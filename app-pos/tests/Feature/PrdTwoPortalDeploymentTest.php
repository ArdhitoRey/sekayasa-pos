<?php

namespace Tests\Feature;

use App\Models\ClientLead;
use App\Models\DeploymentTarget;
use App\Models\ProjectContract;
use App\Models\UatTestCase;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PortalServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrdTwoPortalDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gate_and_one_click_deployment_flow(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Login as Superadmin / Operator
        $user = \App\Models\User::where('role', 'superadmin')->first();
        $this->actingAs($user, 'sanctum');

        // 1. Fetch Portal Leads
        $leadsRes = $this->getJson('/api/portal/leads');
        $leadsRes->assertStatus(200);
        $leads = $leadsRes->json('data');
        $this->assertNotEmpty($leads);

        // 2. Test Payment Gate on Unpaid Client (Apotek Medika Sehat)
        $unpaidClient = ClientLead::where('business_name', 'Apotek Medika Sehat')->first();
        $this->assertNotNull($unpaidClient);

        // Attempting to advance to ready_for_dev without 100% payment MUST FAIL (422)
        $failAdvance = $this->postJson("/api/portal/leads/{$unpaidClient->id}/status", [
            'status' => 'ready_for_dev',
        ]);
        $failAdvance->assertStatus(422);
        $this->assertStringContainsString('Payment Gate', $failAdvance->json('message'));

        // Attempting to deploy production without 100% payment MUST FAIL (422)
        $failDeploy = $this->postJson("/api/deployment/{$unpaidClient->id}/deploy-production");
        $failDeploy->assertStatus(422);

        // 3. Now Verify 100% Payment for Apotek
        $contract = ProjectContract::where('lead_id', $unpaidClient->id)->where('type', 'development')->first();
        $verifyRes = $this->postJson("/api/portal/contracts/{$contract->id}/verify-payment", [
            'payment_reference' => 'BCA-TRF-TEST-100-PERCENT',
        ]);
        $verifyRes->assertStatus(200);

        // Status should now be ready_for_dev
        $unpaidClient->refresh();
        $this->assertEquals('ready_for_dev', $unpaidClient->status);

        // 4. Test One-Click Deploy for Client 1 (Kopi Senopati Artisan - All UAT passed & 100% Paid)
        $client1 = ClientLead::where('business_name', 'Kopi Senopati Artisan')->first();
        $this->assertNotNull($client1);

        // Pre-checks validation
        $preCheckRes = $this->getJson("/api/deployment/{$client1->id}/prechecks?environment=production");
        $preCheckRes->assertStatus(200);
        $this->assertTrue($preCheckRes->json('data.can_deploy'));
        $this->assertCount(7, $preCheckRes->json('data.checks'));

        // Deploy to Staging
        $stagingRes = $this->postJson("/api/deployment/{$client1->id}/deploy-staging");
        $stagingRes->assertStatus(200);
        $this->assertEquals('successful', $stagingRes->json('data.status'));
        $this->assertStringContainsString('Staging', $stagingRes->json('data.log_output'));

        // Deploy to Production (One-Click Deploy)
        $prodRes = $this->postJson("/api/deployment/{$client1->id}/deploy-production", [
            'release_version' => 'v1.0.0-PROD',
        ]);
        $prodRes->assertStatus(200);
        $this->assertEquals('successful', $prodRes->json('data.status'));
        $this->assertNotEmpty($prodRes->json('data.backup_reference'));
        $this->assertStringContainsString('PROD', $prodRes->json('data.log_output'));

        // Client status should now be live
        $client1->refresh();
        $this->assertEquals('live', $client1->status);

        // 5. Test Rollback
        $runId = $prodRes->json('data.id');
        $rollbackRes = $this->postJson("/api/deployment/runs/{$runId}/rollback");
        $rollbackRes->assertStatus(200);
        $this->assertEquals('rolled_back', $rollbackRes->json('data.status'));
    }
}
