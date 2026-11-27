<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_checkout_flow(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Login kasir
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        $outletId = $loginResponse->json('data.user.outlet_id');
        $this->assertNotNull($outletId);

        // 2. Fetch products
        $productResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/products?outlet_id={$outletId}");

        $productResponse->assertStatus(200);
        $products = $productResponse->json('data');
        $this->assertNotEmpty($products);

        $product1 = $products[0];
        $initialStock = $product1['current_stock'];

        // Ensure cashier starts with no open shift, then verify checkout is blocked until shift is opened
        $cashierUser = User::where('email', 'kasir@kopi.id')->firstOrFail();
        CashierShift::where('user_id', $cashierUser->id)->where('status', 'open')->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // 2b. Checkout without opening a shift must be blocked (422 SHIFT_NOT_OPEN)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $outletId,
                'items' => [['product_id' => $product1['id'], 'quantity' => 1]],
                'payment_method' => 'cash',
                'amount_paid' => 200000,
            ])->assertStatus(422)
              ->assertJsonPath('code', 'SHIFT_NOT_OPEN');

        // 2c. Open Cashier Shift first
        $shiftRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/shifts/open', [
                'outlet_id' => $outletId,
                'initial_cash' => 200000,
                'notes' => 'Shift Pagi',
            ])->assertStatus(201);
        $shiftId = $shiftRes->json('data.id');

        // 3. Process Checkout with active shift
        $checkoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $outletId,
                'items' => [
                    [
                        'product_id' => $product1['id'],
                        'quantity' => 2,
                    ],
                ],
                'tax_percentage' => 11,
                'payment_method' => 'cash',
                'amount_paid' => 200000,
                'notes' => 'Meja 04 - Tanpa Gula',
            ]);

        $checkoutResponse->assertStatus(201);
        $invoiceNumber = $checkoutResponse->json('data.transaction.invoice_number');
        $this->assertStringStartsWith("INV-{$outletId}-", $invoiceNumber);
        $this->assertEquals($shiftId, $checkoutResponse->json('data.transaction.shift_id'));
        $this->assertEquals('Meja 04 - Tanpa Gula', $checkoutResponse->json('data.receipt.notes'));

        $this->assertGreaterThan(0, (float) CashierShift::find($shiftId)->cash_sales_total);

        // 4. Verify Stock Deduction
        $inventoryAfter = Inventory::where('outlet_id', $outletId)
            ->where('product_id', $product1['id'])
            ->first();

        $this->assertEquals($initialStock - 2, $inventoryAfter->stock_quantity);

        // 5. Test Dashboard Reporting
        $reportResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/reports/dashboard?outlet_id={$outletId}");

        $reportResponse->assertStatus(200);
        $todaySales = $reportResponse->json('data.today_sales');
        $this->assertGreaterThan(0, $todaySales);
    }
}
