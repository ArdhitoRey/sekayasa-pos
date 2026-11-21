<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\UatTestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_client_endpoints_are_rejected(): void
    {
        $this->getJson('/api/client/overview')->assertStatus(401);
        $this->postJson('/api/client/rent-template', ['plan_id' => 1])->assertStatus(401);
        $this->postJson('/api/client/consultations', [])->assertStatus(401);
    }

    public function test_register_then_request_custom_consultation_with_isolation(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Register new client business first
        $regRes = $this->postJson('/api/register', [
            'business_name' => 'Apotek Harapan Baru',
            'owner_name' => 'Surya Putra',
            'email' => 'surya@apotekharapan.id',
            'phone' => '081299887766',
            'password' => 'password123',
        ]);
        $regRes->assertStatus(201);
        $token = $regRes->json('data.token');
        $this->assertEquals('none', $regRes->json('data.user.business.service_type'));

        // 2. Client checks their dedicated account overview
        $overviewRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/client/overview');
        $overviewRes->assertStatus(200);
        $this->assertNotEmpty($overviewRes->json('data.plans'));

        // 3. Path B: Business process doesn't match template -> Submit Custom Consultation first
        $consultRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Apotek / Kesehatan',
                'scale' => '2-5 Cabang',
                'phone' => '081299887766',
                'preferred_consultation_at' => now()->addDays(2)->toDateTimeString(),
                'template_gap_notes' => 'Butuh pencatatan nomor batch obat, tanggal kadaluarsa, dan tebus resep racikan dokter.',
            ]);
        $consultRes->assertStatus(201);
        $this->assertEquals('consultation_scheduled', $consultRes->json('data.status'));

        // Verify project appears in client's own overview
        $overviewAfterConsult = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/client/overview');
        $this->assertCount(1, $overviewAfterConsult->json('data.projects'));

        // 4. Client Isolation: Surya cannot update UAT belonging to Kopi Senopati Artisan
        $otherUat = UatTestCase::first();
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/client/uat/{$otherUat->id}", ['status' => 'pass'])
            ->assertStatus(403);
    }

    public function test_client_can_create_subscription_invoice_and_superadmin_verifies_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $clientUser = User::where('email', 'klien@usaha.id')->first();
        $superAdmin = User::where('email', 'superadmin@pos.id')->first();
        $plan = Plan::where('slug', 'business')->first();

        // 1. Fresh client creates Subscription invoice
        Sanctum::actingAs($clientUser);
        $rentRes = $this->postJson('/api/client/rent-template', ['plan_id' => $plan->id]);
        $rentRes->assertStatus(201);

        $payment = $rentRes->json('data.payment');
        $this->assertEquals('midtrans_pending', $payment['payment_method']);
        $this->assertEquals('pending', $payment['status']);

        // Before verification, status is pending
        $pollBefore = $this->getJson("/api/client/payments/{$payment['id']}/status");
        $pollBefore->assertStatus(200);
        $this->assertEquals('pending', $pollBefore->json('data.payment_status'));

        // Non-superadmin cannot verify payments (403)
        $this->postJson("/api/admin/subscription-payments/{$payment['id']}/verify")
            ->assertStatus(403);

        // 2. Super Admin verifies subscription payment via Dashboard Admin endpoint
        Sanctum::actingAs($superAdmin);
        $verifyRes = $this->postJson("/api/admin/subscription-payments/{$payment['id']}/verify");
        $verifyRes->assertStatus(200);
        $this->assertEquals('verified', $verifyRes->json('data.payment.status'));

        // 3. Client polling confirms verification & unlocked POS access
        Sanctum::actingAs($clientUser);
        $pollAfter = $this->getJson("/api/client/payments/{$payment['id']}/status");
        $pollAfter->assertStatus(200);
        $this->assertEquals('verified', $pollAfter->json('data.payment_status'));
        $this->assertEquals('template_saas', $pollAfter->json('data.service_type'));
    }
}
