<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    /**
     * Process POS Checkout (FR-021 s/d FR-030)
     * Includes atomic stock locking & shift accumulation
     */
    public function checkout(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $outletId = $request->input('outlet_id', $user->outlet_id);

        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,qris,ewallet',
            'amount_paid' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($user, $businessId, $outletId, $validated) {
            $business = Business::findOrFail($businessId);
            
            // Check active shift for cashier
            $shift = CashierShift::where('outlet_id', $outletId)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            // Calculate Subtotal & Verify Stock atomically
            $subtotal = 0;
            $itemsToProcess = [];

            foreach ($validated['items'] as $itemData) {
                // Lock stock row for update (prevent race conditions)
                $inventory = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $itemData['product_id'])
                    ->lockForUpdate()
                    ->first();

                $product = Product::findOrFail($itemData['product_id']);

                if (!$inventory || $inventory->stock_quantity < $itemData['quantity']) {
                    $available = $inventory ? $inventory->stock_quantity : 0;
                    throw new \Exception("Stok tidak mencukupi untuk '{$product->name}'. Tersedia: {$available}, Diminta: {$itemData['quantity']}");
                }

                $itemDiscount = $itemData['discount_amount'] ?? 0;
                $lineSubtotal = ($product->sell_price * $itemData['quantity']) - $itemDiscount;
                $subtotal += $lineSubtotal;

                $itemsToProcess[] = [
                    'product' => $product,
                    'inventory' => $inventory,
                    'quantity' => $itemData['quantity'],
                    'discount_amount' => $itemDiscount,
                    'line_subtotal' => $lineSubtotal,
                ];
            }

            // Calculate Bill Discount
            $discountTotal = 0;
            if (!empty($validated['discount_type']) && !empty($validated['discount_amount'])) {
                if ($validated['discount_type'] === 'percent') {
                    $discountTotal = ($subtotal * $validated['discount_amount']) / 100;
                } else {
                    $discountTotal = min($validated['discount_amount'], $subtotal);
                }
            }

            // Calculate Tax
            $taxPercentage = $validated['tax_percentage'] ?? ($business->tax_percentage ?? 0);
            $taxableAmount = max(0, $subtotal - $discountTotal);
            $taxAmount = ($taxableAmount * $taxPercentage) / 100;
            $totalAmount = round($taxableAmount + $taxAmount);

            // Validate Payment
            $amountPaid = $validated['amount_paid'];
            if ($amountPaid < $totalAmount) {
                throw new \Exception("Jumlah pembayaran (Rp " . number_format($amountPaid, 0, ',', '.') . ") kurang dari total belanja (Rp " . number_format($totalAmount, 0, ',', '.') . ")");
            }

            $changeAmount = $validated['payment_method'] === 'cash' ? max(0, $amountPaid - $totalAmount) : 0;

            // Generate Unique Invoice Number (INV-OUTLET-Ymd-XXXX)
            $datePrefix = date('Ymd');
            $uniqueCode = strtoupper(Str::random(4));
            $invoiceNumber = "INV-{$outletId}-{$datePrefix}-{$uniqueCode}";

            // 1. Create Transaction
            $transaction = Transaction::create([
                'business_id' => $businessId,
                'outlet_id' => $outletId,
                'cashier_id' => $user->id,
                'shift_id' => $shift ? $shift->id : null,
                'customer_id' => $validated['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_total' => $discountTotal,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'paid',
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Insert Items & Deduct Stock with Movement Log
            foreach ($itemsToProcess as $item) {
                $product = $item['product'];
                $inventory = $item['inventory'];
                $qty = $item['quantity'];

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'buy_price' => $product->buy_price,
                    'sell_price' => $product->sell_price,
                    'quantity' => $qty,
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['line_subtotal'],
                ]);

                // Update Stock
                $beforeStock = $inventory->stock_quantity;
                $afterStock = $beforeStock - $qty;
                $inventory->update(['stock_quantity' => $afterStock]);

                // Stock Movement Record
                StockMovement::create([
                    'business_id' => $businessId,
                    'outlet_id' => $outletId,
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity_before' => $beforeStock,
                    'quantity_change' => -$qty,
                    'quantity_after' => $afterStock,
                    'reason' => "Penjualan Struk #{$invoiceNumber}",
                    'user_id' => $user->id,
                    'reference_type' => 'Transaction',
                    'reference_id' => $transaction->id,
                ]);
            }

            // 3. Record Payment
            $payment = Payment::create([
                'transaction_id' => $transaction->id,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // 4. Update Shift Totals
            if ($shift) {
                if ($validated['payment_method'] === 'cash') {
                    $shift->increment('cash_sales_total', $totalAmount);
                } else {
                    $shift->increment('non_cash_sales_total', $totalAmount);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil diproses',
                'data' => [
                    'transaction' => $transaction->load(['items', 'payment', 'customer', 'outlet', 'cashier']),
                    'receipt' => [
                        'invoice_number' => $transaction->invoice_number,
                        'date' => $transaction->created_at->format('d/m/Y H:i'),
                        'business_name' => $business->name,
                        'outlet_name' => $transaction->outlet->name,
                        'outlet_address' => $transaction->outlet->address,
                        'outlet_phone' => $transaction->outlet->phone,
                        'cashier_name' => $user->name,
                        'customer_name' => $transaction->customer ? $transaction->customer->name : 'Pelanggan Umum',
                        'items' => $transaction->items->map(function ($it) {
                            return [
                                'name' => $it->product_name,
                                'qty' => $it->quantity,
                                'price' => $it->sell_price,
                                'subtotal' => $it->subtotal,
                            ];
                        }),
                        'subtotal' => $subtotal,
                        'discount_total' => $discountTotal,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'payment_method' => strtoupper($validated['payment_method']),
                        'amount_paid' => $amountPaid,
                        'change_amount' => $changeAmount,
                        'footer' => $business->receipt_footer,
                    ],
                ],
            ], 201);
        });
    }

    /**
     * Hold Transaction (FR-027)
     */
    public function holdTransaction(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        $invoiceNumber = "HOLD-" . strtoupper(Str::random(6));

        $transaction = Transaction::create([
            'business_id' => $user->business_id,
            'outlet_id' => $validated['outlet_id'],
            'cashier_id' => $user->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'subtotal' => 0,
            'total_amount' => 0,
            'payment_status' => 'held',
            'notes' => json_encode([
                'items' => $validated['items'],
                'user_notes' => $validated['notes'] ?? '',
            ]),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil ditahan',
            'data' => $transaction,
        ]);
    }

    /**
     * List Held Transactions (FR-027)
     */
    public function listHeld(Request $request)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $helds = Transaction::where('business_id', $user->business_id)
            ->where('outlet_id', $outletId)
            ->where('payment_status', 'held')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $helds,
        ]);
    }

    /**
     * Resume / Cancel Held Transaction (FR-027)
     */
    public function resumeHeld($id)
    {
        $transaction = Transaction::where('payment_status', 'held')->findOrFail($id);
        $data = json_decode($transaction->notes, true);
        $transaction->delete(); // Remove hold entry

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Transaction History (FR-032)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $query = Transaction::where('business_id', $user->business_id)
            ->where('outlet_id', $outletId)
            ->where('payment_status', '!=', 'held')
            ->with(['items', 'payment', 'customer', 'cashier'])
            ->latest();

        if ($date = $request->query('date')) {
            $query->whereDate('created_at', $date);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    /**
     * Transaction Detail (FR-033)
     */
    public function detail($id)
    {
        $transaction = Transaction::with(['items', 'payment', 'customer', 'outlet', 'cashier'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ]);
    }

    /**
     * Void / Refund Transaction (FR-034)
     */
    public function voidTransaction(Request $request, $id)
    {
        $user = $request->user();
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return DB::transaction(function () use ($user, $id, $validated) {
            $transaction = Transaction::where('business_id', $user->business_id)
                ->with('items')
                ->findOrFail($id);

            if ($transaction->payment_status === 'voided') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaksi ini sudah pernah dibatalkan sebelumnya.',
                ], 400);
            }

            // Restore Stock
            foreach ($transaction->items as $item) {
                $inv = Inventory::where('outlet_id', $transaction->outlet_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($inv) {
                    $before = $inv->stock_quantity;
                    $after = $before + $item->quantity;
                    $inv->update(['stock_quantity' => $after]);

                    StockMovement::create([
                        'business_id' => $user->business_id,
                        'outlet_id' => $transaction->outlet_id,
                        'product_id' => $item->product_id,
                        'type' => 'void',
                        'quantity_before' => $before,
                        'quantity_change' => $item->quantity,
                        'quantity_after' => $after,
                        'reason' => "Void Transaksi #{$transaction->invoice_number}: {$validated['reason']}",
                        'user_id' => $user->id,
                        'reference_type' => 'Transaction',
                        'reference_id' => $transaction->id,
                    ]);
                }
            }

            $transaction->update(['payment_status' => 'voided']);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.',
            ]);
        });
    }
}
