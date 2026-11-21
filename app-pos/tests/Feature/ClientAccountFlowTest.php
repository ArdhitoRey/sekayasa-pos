<?php

namespace Tests\Feature;

use App\Models\ClientLead;
use App\Models\Plan;
use App\Models\UatTestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_register_then_request_custom_consultation_and_rent_template(): void
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

        // 5. Path A: Fresh client creates Subscription invoice (Midtrans pending -> Portal admin verifies)
        $freshLogin = $this->postJson('/api/login', [
            'email' => 'klien@usaha.id',
            'password' => 'password123',
        ]);
        $freshLogin->assertStatus(200);
        $freshToken = $freshLogin->json('data.token');
        $plan = Plan::where('slug', 'business')->first();

        $rentRes = $this->withHeader('Authorization', "Bearer {$freshToken}")
            ->postJson('/api/client/rent-template', ['plan_id' => $plan->id]);
        $rentRes->assertStatus(201);

        $payment = $rentRes->json('data.payment');
        $this->assertEquals('midtrans_pending', $payment['payment_method']);
        $this->assertEquals('pending', $payment['status']);

        // Before verification, status is pending
        $pollBefore = $this->withHeader('Authorization', "Bearer {$freshToken}")
            ->getJson("/api/client/payments/{$payment['id']}/status");
        $pollBefore->assertStatus(200);
        $this->assertEquals('pending', $pollBefore->json('data.payment_status'));

        // Portal Admin verifies subscription payment while Midtrans is in application
        $verifyRes = $this->postJson("/api/portal/subscription-payments/{$payment['id']}/verify");
        $verifyRes->assertStatus(200);
        $this->assertEquals('verified', $verifyRes->json('data.payment.status'));

        // Polling confirms verification & unlocked POS access
        $pollAfter = $this->withHeader('Authorization', "Bearer {$freshToken}")
            ->getJson("/api/client/payments/{$payment['id']}/status");
        $pollAfter->assertStatus(200);
        $this->assertEquals('verified', $pollAfter->json('data.payment_status'));
        $this->assertEquals('template_saas', $pollAfter->json('data.service_type'));
    }
}
