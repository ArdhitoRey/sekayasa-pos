<?php

namespace Tests\Feature;

use App\Mail\AuthOtpMail;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\Transaction;
use App\Models\UatTestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    private function verifyEmailFor(string $email, string $ownerName = 'Owner'): string
    {
        $capturedCode = null;
        Mail::fake();

        $this->postJson('/api/email/send-verification', [
            'email' => $email,
            'owner_name' => $ownerName,
        ])->assertStatus(200);

        Mail::assertSent(AuthOtpMail::class, function (AuthOtpMail $mail) use ($email, &$capturedCode) {
            if (strtolower($email) === strtolower($mail->to[0]['address'] ?? '')) {
                $capturedCode = $mail->code;
                return true;
            }
            return false;
        });

        $this->assertNotNull($capturedCode, "OTP should have been sent to {$email}");
        return (string) $capturedCode;
    }

    public function test_unauthenticated_client_endpoints_are_rejected(): void
    {
        $this->getJson('/api/client/overview')->assertStatus(401);
        $this->postJson('/api/client/rent-template', ['plan_id' => 1])->assertStatus(401);
        $this->postJson('/api/client/consultations', [])->assertStatus(401);
    }

    public function test_register_then_request_custom_consultation_with_isolation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $otp = $this->verifyEmailFor('surya@apotekharapan.id', 'Surya Putra');

        // 1. Register new client business first
        $regRes = $this->postJson('/api/register', [
            'business_name' => 'Apotek Harapan Baru',
            'owner_name' => 'Surya Putra',
            'email' => 'surya@apotekharapan.id',
            'verification_code' => $otp,
            'phone' => '081299887766',
            'password' => 'password123',
        ]);
        $regRes->assertStatus(201);
        $token = $regRes->json('data.token');
        $this->assertEquals('none', $regRes->json('data.user.business.service_type'));
        $this->assertEquals(0, \App\Models\Subscription::where('business_id', $regRes->json('data.user.business_id'))->count());
        $this->assertEquals('Belum Berlangganan', $regRes->json('data.user.business.subscription_summary.plan_name'));
        $this->assertEquals('none', $regRes->json('data.user.business.subscription_summary.lifecycle_status'));
        $this->assertFalse((bool) $regRes->json('data.user.business.has_active_subscription'));

        // 2. Client checks their dedicated account overview
        $overviewRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/client/overview');
        $overviewRes->assertStatus(200);
        $this->assertNotEmpty($overviewRes->json('data.plans'));

        // 3. Submit Feature Consultation respecting H-3 minimum schedule & working hours
        $consultRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Apotek / Kesehatan',
                'scale' => '2-5 Cabang',
                'phone' => '081299887766',
                'preferred_date' => now()->addDays(4)->format('Y-m-d'),
                'preferred_time' => '10:00',
                'template_gap_notes' => 'Butuh pencatatan nomor batch obat, tanggal kadaluarsa, dan tebus resep racikan dokter.',
            ]);
        $consultRes->assertStatus(201);
        $this->assertEquals('consultation_scheduled', $consultRes->json('data.status'));
        $this->assertStringContainsString('https://wa.me/', $consultRes->json('whatsapp_url'));

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

        // Before verification, status is pending and receipt is locked (403)
        $pollBefore = $this->getJson("/api/client/payments/{$payment['id']}/status");
        $pollBefore->assertStatus(200);
        $this->assertEquals('pending', $pollBefore->json('data.payment_status'));
        $this->getJson("/api/client/payments/{$payment['id']}/receipt")->assertStatus(403);

        // Non-superadmin cannot verify payments (403)
        $this->postJson("/api/admin/subscription-payments/{$payment['id']}/verify")
            ->assertStatus(403);

        // 2. Super Admin verifies subscription payment via Dashboard Admin endpoint
        Sanctum::actingAs($superAdmin);
        $verifyRes = $this->postJson("/api/admin/subscription-payments/{$payment['id']}/verify");
        $verifyRes->assertStatus(200);
        $this->assertEquals('verified', $verifyRes->json('data.payment.status'));
        $this->assertStringStartsWith('KW-', $verifyRes->json('data.payment.receipt_number'));

        // 3. Client polling confirms verification, unlocked POS access, and official printable Nota Lunas (200)
        Sanctum::actingAs($clientUser);
        $pollAfter = $this->getJson("/api/client/payments/{$payment['id']}/status");
        $pollAfter->assertStatus(200);
        $this->assertEquals('verified', $pollAfter->json('data.payment_status'));
        $this->assertEquals('template_saas', $pollAfter->json('data.service_type'));
        $this->assertNotNull($pollAfter->json('data.receipt_number'));

        $receiptRes = $this->getJson("/api/client/payments/{$payment['id']}/receipt")
            ->assertStatus(200);
        $this->assertEquals('LUNAS / TERVERIFIKASI', $receiptRes->json('data.status'));
        $this->assertEquals($verifyRes->json('data.payment.receipt_number'), $receiptRes->json('data.receipt_number'));
        $this->assertEquals('Business', $receiptRes->json('data.plan_name'));
    }

    public function test_detailed_registration_and_duplicate_user_prevention(): void
    {
        $this->seed(DatabaseSeeder::class);

        $otp1 = $this->verifyEmailFor('Andi@KopiSby.id', 'Andi Wijaya');

        // 1. Register with full corporate, legal, PIC, & first outlet details
        $res = $this->postJson('/api/register', [
            'business_name' => 'Kopi Kenangan Surabaya',
            'legal_type' => 'PT',
            'business_category' => 'F&B / Restoran / Kafe',
            'owner_name' => 'Andi Wijaya',
            'job_title' => 'Direktur Utama',
            'nik' => '3578012345678901',
            'email' => 'Andi@KopiSby.id',
            'verification_code' => $otp1,
            'phone' => '+62 812-5555-6666',
            'npwp' => '09.123.456.7-611.000',
            'nib' => '1234567890123',
            'outlet_name' => 'Outlet Pusat Gubeng',
            'outlet_code' => 'SBY-01',
            'address' => 'Jl. Raya Gubeng No. 88, Surabaya',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
            'postal_code' => '60281',
            'outlet_count_estimate' => '2-5 Cabang',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 10,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res->assertStatus(201);
        $token = $res->json('data.token');
        $this->assertEquals('Outlet Pusat Gubeng', $res->json('data.user.outlet.name'));
        $this->assertEquals('SBY-01', $res->json('data.user.outlet.outlet_code'));
        $this->assertEquals('Kota Surabaya', $res->json('data.user.outlet.city'));
        $this->assertEquals('PT', $res->json('data.user.business.legal_type'));
        $this->assertEquals('3578012345678901', $res->json('data.user.nik'));
        $this->assertEquals('081255556666', $res->json('data.user.phone'));

        // 2. Duplicate registration using same email (case-insensitive) -> rejected (422)
        $this->postJson('/api/register', [
            'business_name' => 'Usaha Kedua',
            'owner_name' => 'Andi Wijaya',
            'nik' => '3578012345678999',
            'email' => 'andi@kopisby.id',
            'verification_code' => '123456',
            'phone' => '081999990000',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        // 3. Duplicate registration using same phone in different format & different email -> rejected (422)
        $otp2 = $this->verifyEmailFor('andi2@kopisby.id', 'Andi Kedua');
        $this->postJson('/api/register', [
            'business_name' => 'Usaha Ketiga',
            'owner_name' => 'Andi Kedua',
            'nik' => '3578012345678988',
            'email' => 'andi2@kopisby.id',
            'verification_code' => $otp2,
            'phone' => '081255556666',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['phone']);

        // 4. Duplicate registration using same NIK -> rejected (422)
        $this->postJson('/api/register', [
            'business_name' => 'Usaha Keempat',
            'owner_name' => 'Andi Ketiga',
            'nik' => '3578012345678901',
            'email' => 'andi3@kopisby.id',
            'verification_code' => '123456',
            'phone' => '08177778888',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['nik']);

        // 4. Logged-in user attempting to register again -> rejected (403)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/register', [
                'business_name' => 'Usaha Baru',
                'owner_name' => 'User Lain',
                'email' => 'baru@unik.id',
                'phone' => '08133334444',
                'password' => 'password123',
            ])->assertStatus(403);
    }

    public function test_cloudflare_turnstile_blocks_bots_and_allows_verified_humans(): void
    {
        $this->seed(DatabaseSeeder::class);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);

        // 1. Missing token -> blocked (422)
        $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);

        $this->postJson('/api/register', [
            'business_name' => 'Bot Shop',
            'owner_name' => 'Bot',
            'email' => 'bot@spam.id',
            'phone' => '08155551234',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);

        // 2. Verify Cloudflare siteverify distinguishes fake bot tokens vs verified human tokens
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => function ($request) {
                return Http::response([
                    'success' => $request['response'] === 'valid-human-token',
                ], 200);
            },
        ]);

        // Fake / rejected token -> blocked (422)
        $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
            'cf_turnstile_response' => 'invalid-bot-token',
        ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);

        // Verified human token -> allowed (200)
        $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
            'cf_turnstile_response' => 'valid-human-token',
        ])->assertStatus(200);

        Http::assertSent(fn ($req) => $req->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    }

    public function test_superadmin_can_monitor_all_clients_logs_and_toggle_business_status(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(\Database\Seeders\DominantBarbershopSeeder::class);

        $ownerToken = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        // Non-superadmin must not access platform monitoring
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson('/api/admin/platform-overview')
            ->assertStatus(403);

        $this->app['auth']->forgetGuards();

        // Trigger a failed login to populate activity log tracking
        $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $superadminToken = $this->postJson('/api/login', [
            'email' => 'superadmin@pos.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();

        $overview = $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->getJson('/api/admin/platform-overview')
            ->assertStatus(200);

        $businessNames = collect($overview->json('data.businesses'))->pluck('name')->all();
        $this->assertContains('Kopi & Mart Barista', $businessNames);
        $this->assertContains('Dominant Barbershop', $businessNames);
        $this->assertNotEmpty($overview->json('data.metrics.total_businesses'));
        $this->assertIsArray($overview->json('data.error_logs'));

        $actions = collect($overview->json('data.activity_logs'))->pluck('action')->all();
        $this->assertContains('login_failed', $actions);
        $this->assertContains('login_success', $actions);

        $barbershop = collect($overview->json('data.businesses'))->firstWhere('name', 'Dominant Barbershop');
        $this->assertTrue((bool) $barbershop['is_active']);

        $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->postJson("/api/admin/businesses/{$barbershop['id']}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Reactivate so state stays clean
        $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->postJson("/api/admin/businesses/{$barbershop['id']}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);

        // Verify Client Detail Endpoint + Subscription Expiry & Extension Tracking
        $detail = $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->getJson("/api/admin/businesses/{$barbershop['id']}")
            ->assertStatus(200);

        $this->assertEquals('Dominant Barbershop', $detail->json('data.business.name'));
        $this->assertNotEmpty($detail->json('data.users'));
        $this->assertNotEmpty($detail->json('data.subscription_summary'));

        $starterPlan = Plan::where('slug', 'starter')->first();
        $ext1 = $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->postJson("/api/admin/businesses/{$barbershop['id']}/extend-subscription", [
                'plan_id' => $starterPlan->id,
                'days' => 30,
                'status' => 'active',
            ])
            ->assertStatus(200);
        $daysAfterFirstExtend = (int) $ext1->json('data.subscription_summary.days_remaining');
        $this->assertGreaterThanOrEqual(29, $daysAfterFirstExtend);

        // Extending again should add on top of remaining days (smart renewal stacking)
        $ext2 = $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->postJson("/api/admin/businesses/{$barbershop['id']}/extend-subscription", [
                'plan_id' => $starterPlan->id,
                'days' => 30,
                'status' => 'active',
            ])
            ->assertStatus(200);
        $this->assertGreaterThanOrEqual(59, (int) $ext2->json('data.subscription_summary.days_remaining'));

        // Verify Client self-access (/api/me & /api/client/overview) exposes subscription_summary & usage
        $this->app['auth']->forgetGuards();
        $meRes = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson('/api/me')
            ->assertStatus(200);
        $this->assertEquals('Business', $meRes->json('data.business.subscription_summary.plan_name'));
        $this->assertNotNull($meRes->json('data.business.subscription_summary.expires_at_formatted'));

        $clientOverview = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson('/api/client/overview')
            ->assertStatus(200);
        $this->assertEquals('Business', $clientOverview->json('data.subscription_summary.plan_name'));
        $this->assertGreaterThan(0, (int) $clientOverview->json('data.usage.products_count'));

        // Verify POS failed checkout (when stock insufficient on active cashier shift) logs `pos_checkout_failed` for Super Admin anomaly tracking
        $owner = User::where('email', 'owner@kopi.id')->first();
        $cashier = User::where('email', 'kasir@kopi.id')->firstOrFail();
        $product = \App\Models\Product::where('business_id', $owner->business_id)->first();
        \App\Models\CashierShift::where('user_id', $cashier->id)->where('status', 'open')->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        $this->app['auth']->forgetGuards();
        $cashierToken = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->postJson('/api/shifts/open', [
                'outlet_id' => $cashier->outlet_id,
                'initial_cash' => 100000,
            ])->assertStatus(201);
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $cashier->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 999999]],
                'payment_method' => 'cash',
                'amount_paid' => 1000000,
            ])->assertStatus(500);

        $this->app['auth']->forgetGuards();
        $overviewAfterError = $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->getJson('/api/admin/platform-overview')
            ->assertStatus(200);
        $actionsAfterError = collect($overviewAfterError->json('data.activity_logs'))->pluck('action')->all();
        $this->assertContains('pos_checkout_failed', $actionsAfterError);
        $this->assertContains('admin_extend_subscription', $actionsAfterError);

        // Non-superadmin cannot delete a client business
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->deleteJson("/api/admin/businesses/{$barbershop['id']}")
            ->assertStatus(403);

        // Super Admin deletes client business permanently and cascades users/outlets/leads
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$superadminToken}")
            ->deleteJson("/api/admin/businesses/{$barbershop['id']}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('businesses', ['id' => $barbershop['id']]);
        $this->assertDatabaseMissing('users', ['email' => 'owner@dominantbarbershop.id']);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin_delete_business',
            'entity_id' => $barbershop['id'],
        ]);
    }

    public function test_registration_requires_verified_email_and_supports_forgot_reset_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Missing verification_code must fail with 422
        $this->postJson('/api/register', [
            'business_name' => 'Warung Test',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@warungtest.id',
            'phone' => '081288990011',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['verification_code']);

        // Wrong OTP must fail with 422
        $realOtp = $this->verifyEmailFor('budi@warungtest.id', 'Budi Santoso');
        $wrongOtp = $realOtp === '000000' ? '111111' : '000000';
        $this->postJson('/api/register', [
            'business_name' => 'Warung Test',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@warungtest.id',
            'verification_code' => $wrongOtp,
            'phone' => '081288990011',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['verification_code']);

        // Valid OTP registers with verified email
        $this->postJson('/api/register', [
            'business_name' => 'Warung Test',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@warungtest.id',
            'verification_code' => $realOtp,
            'phone' => '081288990011',
            'password' => 'secret123',
        ])->assertStatus(201);

        $user = User::where('email', 'budi@warungtest.id')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);

        // Forgot Password sends reset OTP and allows password update
        $resetCode = null;
        Mail::fake();
        $this->postJson('/api/password/forgot', ['email' => 'budi@warungtest.id'])
            ->assertStatus(200);
        Mail::assertSent(AuthOtpMail::class, function (AuthOtpMail $mail) use (&$resetCode) {
            if ($mail->purpose === 'reset_password') {
                $resetCode = $mail->code;
                return true;
            }
            return false;
        });
        $this->assertNotNull($resetCode);

        $this->postJson('/api/password/reset', [
            'email' => 'budi@warungtest.id',
            'code' => $resetCode,
            'password' => 'newpass999',
            'password_confirmation' => 'newpass999',
        ])->assertStatus(200);

        $this->postJson('/api/login', [
            'email' => 'budi@warungtest.id',
            'password' => 'secret123',
        ])->assertStatus(422);

        $this->postJson('/api/login', [
            'email' => 'budi@warungtest.id',
            'password' => 'newpass999',
        ])->assertStatus(200);
    }

    public function test_google_oauth_logs_in_existing_user_and_enforces_full_registration_for_new_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => '/auth/google/callback',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => function ($request) {
                $token = $request['code'] === 'auth-code-2' ? 'tok-new' : 'tok-existing';
                return Http::response(['access_token' => $token], 200);
            },
            'https://openidconnect.googleapis.com/v1/userinfo' => function ($request) {
                $auth = $request->header('Authorization')[0] ?? '';
                if (str_contains($auth, 'tok-new')) {
                    return Http::response([
                        'sub' => 'google-sub-new-user',
                        'email' => 'pendatangbaru@gmail.com',
                        'name' => 'Rina Google',
                        'picture' => 'https://example.com/rina.jpg',
                    ], 200);
                }
                return Http::response([
                    'sub' => 'google-sub-owner',
                    'email' => 'owner@kopi.id',
                    'name' => 'Owner Kopi',
                    'picture' => 'https://example.com/avatar.jpg',
                ], 200);
            },
        ]);

        // 1. Existing user logs straight in via Google
        $redirectRes = $this->get('/auth/google')->assertStatus(302);
        parse_str((string) parse_url($redirectRes->headers->get('Location'), PHP_URL_QUERY), $oauthParams);
        $callbackRes = $this->get('/auth/google/callback?state=' . $oauthParams['state'] . '&code=auth-code-1')
            ->assertStatus(302);
        parse_str((string) parse_url($callbackRes->headers->get('Location'), PHP_URL_QUERY), $cbParams);
        $this->assertArrayHasKey('google_exchange', $cbParams);

        $exchange = $this->getJson('/api/auth/google/exchange/' . $cbParams['google_exchange'])
            ->assertStatus(200);
        $this->assertEquals('login', $exchange->json('data.type'));
        $this->assertNotEmpty($exchange->json('data.token'));

        // 2. New Google user gets pending registration token & MUST complete full business form
        $redirectNew = $this->get('/auth/google')->assertStatus(302);
        parse_str((string) parse_url($redirectNew->headers->get('Location'), PHP_URL_QUERY), $oauthNewParams);
        $cbNew = $this->get('/auth/google/callback?state=' . $oauthNewParams['state'] . '&code=auth-code-2')
            ->assertStatus(302);
        parse_str((string) parse_url($cbNew->headers->get('Location'), PHP_URL_QUERY), $cbNewParams);
        $this->assertArrayHasKey('google_pending', $cbNewParams);

        $pendingRes = $this->getJson('/api/auth/google/exchange/' . $cbNewParams['google_pending'])
            ->assertStatus(200);
        $this->assertEquals('pending_registration', $pendingRes->json('data.type'));
        $pendingToken = $pendingRes->json('data.google_pending_token');

        // Submitting without required business fields must still fail (422)
        $this->postJson('/api/register', [
            'google_pending_token' => $pendingToken,
            'email' => 'pendatangbaru@gmail.com',
            'owner_name' => 'Rina Google',
        ])->assertStatus(422)->assertJsonValidationErrors(['business_name']);

        // Completing full business data succeeds without needing separate password/OTP
        $regGoogle = $this->postJson('/api/register', [
            'google_pending_token' => $pendingToken,
            'business_name' => 'Kopi Rina Mandiri',
            'owner_name' => 'Rina Google',
            'nik' => '3578099988887777',
            'email' => 'pendatangbaru@gmail.com',
            'phone' => '081266667777',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
        ])->assertStatus(201);
        $this->assertEquals('google-sub-new-user', User::where('email', 'pendatangbaru@gmail.com')->value('google_id'));
        $this->assertNotEmpty($regGoogle->json('data.token'));
    }

    public function test_owner_staff_quota_and_role_permissions_owner_vs_optional_admin_vs_cashier(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        // Starter business: Budi (owner@kopi.id has Business plan: 10 users, 3 outlets). Let's test on Business AND Starter quota!
        $ownerToken = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $owner = User::where('email', 'owner@kopi.id')->firstOrFail();

        // Owner sees staff & quotas
        $this->app['auth']->forgetGuards();
        $staffIdx = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson('/api/staff')
            ->assertStatus(200);
        $this->assertEquals('Business', $staffIdx->json('data.limits.plan_name'));

        // Owner adds an optional Admin via /api/staff
        $this->app['auth']->forgetGuards();
        $adminCreate = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->postJson('/api/staff', [
                'name' => 'Siti Admin Toko',
                'email' => 'admin.baru@kopi.id',
                'role' => 'admin',
                'outlet_id' => $owner->outlet_id,
                'password' => 'password123',
            ])->assertStatus(201);
        $this->assertEquals('admin', $adminCreate->json('data.role'));

        // Login as Admin -> allowed on operations (sales report, inventory), blocked (403) on Owner-exclusive (staff, outlets, profit HPP, billing)
        $this->app['auth']->forgetGuards();
        $adminToken = $this->postJson('/api/login', [
            'email' => 'admin.baru@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/reports/sales')->assertStatus(200);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/reports/profit')->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/staff')->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->postJson('/api/outlets', ['name' => 'Cabang Gelap'])->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->postJson('/api/client/rent-template', ['plan_id' => 1])->assertStatus(403);

        // Login as Cashier -> blocked (403) from creating products, adjusting stock, viewing sales/profit reports, staff, outlets, billing
        $this->app['auth']->forgetGuards();
        $cashierToken = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$cashierToken}")->postJson('/api/products', [
            'name' => 'Ilegal',
            'buy_price' => 1000,
            'sell_price' => 2000,
            'image' => UploadedFile::fake()->image('menu.png'),
        ])->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$cashierToken}")->getJson('/api/reports/sales')->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$cashierToken}")->getJson('/api/staff')->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$cashierToken}")->postJson('/api/client/rent-template', ['plan_id' => 1])->assertStatus(403);

        // Test Starter plan business with NO Admin (admin is optional -> owner still retains full operations + owner access) & max_users = 2 limit
        $starterOwner = User::where('email', 'klien@usaha.id')->firstOrFail();
        $starterPlan = Plan::where('slug', 'starter')->firstOrFail();
        \App\Models\Subscription::create([
            'business_id' => $starterOwner->business_id,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        $this->app['auth']->forgetGuards();
        $starterToken = $this->postJson('/api/login', [
            'email' => 'klien@usaha.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();
        $starterStaff = $this->withHeader('Authorization', "Bearer {$starterToken}")->getJson('/api/staff')->assertStatus(200);
        $this->assertFalse((bool) $starterStaff->json('data.limits.has_admin'));
        $this->withHeader('Authorization', "Bearer {$starterToken}")->getJson('/api/reports/profit')->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$starterToken}")->postJson('/api/staff', [
            'name' => 'Kasir Satu',
            'email' => 'kasir1@usaha.id',
            'role' => 'cashier',
            'outlet_id' => $starterOwner->outlet_id,
            'password' => 'password123',
        ])->assertStatus(201);

        // 3rd user exceeds Starter limit (2 users) -> 403
        $this->withHeader('Authorization', "Bearer {$starterToken}")->postJson('/api/staff', [
            'name' => 'Kasir Dua Overlimit',
            'email' => 'kasir2@usaha.id',
            'role' => 'cashier',
            'outlet_id' => $starterOwner->outlet_id,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_product_creation_requires_image_and_supports_image_replacement(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $ownerToken = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');
        $owner = User::where('email', 'owner@kopi.id')->firstOrFail();

        // Creating product without image must fail 422
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->postJson('/api/products', [
                'name' => 'Matcha Latte Tanpa Foto',
                'buy_price' => 12000,
                'sell_price' => 28000,
                'outlet_id' => $owner->outlet_id,
            ])->assertStatus(422)->assertJsonValidationErrors(['image']);

        // Creating product WITH image succeeds & stores file under storage/products + exposes image_url
        $img1 = UploadedFile::fake()->image('matcha.jpg', 400, 400);
        $createRes = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->post('/api/products', [
                'name' => 'Matcha Latte Ceremonial',
                'buy_price' => 12000,
                'sell_price' => 28000,
                'initial_stock' => 25,
                'outlet_id' => $owner->outlet_id,
                'image' => $img1,
            ])->assertStatus(201);

        $productId = $createRes->json('data.id');
        $storedPath1 = $createRes->json('data.image_path');
        $this->assertNotEmpty($storedPath1);
        Storage::disk('public')->assertExists($storedPath1);
        $this->assertStringContainsString('/storage/' . $storedPath1, $createRes->json('data.image_url'));

        // Updating product via POST /api/products/{id} replaces image cleanly
        $img2 = UploadedFile::fake()->image('matcha-v2.png', 500, 500);
        $updateRes = $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->post("/api/products/{$productId}", [
                'name' => 'Matcha Latte Ceremonial V2',
                'image' => $img2,
            ])->assertStatus(200);

        $storedPath2 = $updateRes->json('data.image_path');
        $this->assertNotEquals($storedPath1, $storedPath2);
        Storage::disk('public')->assertExists($storedPath2);
    }

    public function test_newly_registered_business_starts_with_zero_categories_and_custom_category_crud_with_products(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $otp = $this->verifyEmailFor('owner.barbershop@tandha.id', 'Dimas Haircut');
        $regRes = $this->postJson('/api/register', [
            'business_name' => 'Barbershop Dimas',
            'owner_name' => 'Dimas Haircut',
            'email' => 'owner.barbershop@tandha.id',
            'verification_code' => $otp,
            'phone' => '081234560001',
            'password' => 'password123',
        ])->assertStatus(201);

        $token = $regRes->json('data.token');
        $outletId = $regRes->json('data.user.outlet_id');
        $businessId = $regRes->json('data.user.business_id');

        // 1. Newly registered business MUST start with 0 categories (never pre-seeded)
        $this->assertEquals(0, Category::where('business_id', $businessId)->count());
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/categories')
            ->assertStatus(200)
            ->assertExactJson(['status' => 'success', 'data' => []]);

        // 2. Owner creates custom categories; duplicates are rejected (422)
        $catCreate = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/categories', [
                'name' => 'Potong & Styling',
                'description' => 'Layanan pangkas rambut dan styling pria',
            ])->assertStatus(201);
        $catId = $catCreate->json('data.id');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/categories', [
                'name' => 'Potong & Styling',
            ])->assertStatus(422);

        // 3. Create product inside that custom category and verify /api/categories returns products list + count
        $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/products', [
                'name' => 'Gentleman Haircut + Wash',
                'category_id' => $catId,
                'buy_price' => 10000,
                'sell_price' => 65000,
                'initial_stock' => 99,
                'outlet_id' => $outletId,
                'image' => UploadedFile::fake()->image('haircut.png', 300, 300),
            ])->assertStatus(201);

        $catIndex = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/categories')
            ->assertStatus(200);
        $catRow = $catIndex->json('data.0');
        $this->assertEquals('Potong & Styling', $catRow['name']);
        $this->assertEquals(1, $catRow['products_count']);
        $this->assertCount(1, $catRow['products']);
        $this->assertEquals('Gentleman Haircut + Wash', $catRow['products'][0]['name']);

        // 4. Update & Delete category (deleting category keeps product intact as uncategorized)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/categories/{$catId}", [
                'name' => 'Haircut & Grooming',
                'description' => 'Diperbarui',
            ])->assertStatus(200)
              ->assertJsonPath('data.name', 'Haircut & Grooming');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/categories/{$catId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'business_id' => $businessId,
            'name' => 'Gentleman Haircut + Wash',
            'category_id' => null,
        ]);
    }

    public function test_owner_feature_settings_control_cashier_and_admin_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ownerToken = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');
        $owner = User::where('email', 'owner@kopi.id')->firstOrFail();

        // Create an Admin account under Budi's business
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->postJson('/api/staff', [
                'name' => 'Admin Switch Test',
                'email' => 'admin.switch@kopi.id',
                'role' => 'admin',
                'outlet_id' => $owner->outlet_id,
                'password' => 'password123',
            ])->assertStatus(201);

        // Disable Cashier discount, void, shift requirement, and Admin category/sales/shift/product permissions
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->putJson('/api/business/settings', [
                'feature_settings' => [
                    'pos_allow_discount' => false,
                    'pos_allow_customer' => false,
                    'pos_allow_notes' => false,
                    'pos_allow_barcode' => false,
                    'pos_allow_history' => true,
                    'pos_allow_void' => false,
                    'pos_require_shift' => false,
                    'admin_view_dashboard' => true,
                    'admin_manage_products' => false,
                    'admin_manage_categories' => false,
                    'admin_manage_inventory' => true,
                    'admin_view_sales_report' => false,
                    'admin_view_shift_history' => false,
                ],
            ])->assertStatus(200);

        // Admin blocked (403) on toggled-off endpoints, allowed on dashboard/inventory
        $this->app['auth']->forgetGuards();
        $adminToken = $this->postJson('/api/login', [
            'email' => 'admin.switch@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/reports/dashboard')->assertStatus(200);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/inventory/movements')->assertStatus(200);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->postJson('/api/categories', ['name' => 'Blocked'])->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/reports/sales')->assertStatus(403);
        $this->withHeader('Authorization', "Bearer {$adminToken}")->getJson('/api/shifts/history')->assertStatus(403);

        // Cashier behavior reflects Owner settings
        $this->app['auth']->forgetGuards();
        $cashierToken = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        $product = Product::where('business_id', $owner->business_id)->firstOrFail();

        // Checkout with discount must fail 403 for cashier when pos_allow_discount=false
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $owner->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'discount_type' => 'fixed',
                'discount_amount' => 1000,
                'payment_method' => 'cash',
                'amount_paid' => 100000,
            ])->assertStatus(403);

        // Checkout without open shift succeeds (201) when pos_require_shift=false
        \App\Models\CashierShift::where('outlet_id', $owner->outlet_id)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now()]);
        $checkoutRes = $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $owner->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'amount_paid' => 100000,
            ])->assertStatus(201);
        $txId = $checkoutRes->json('data.transaction.id');

        // Voiding must fail 403 for cashier when pos_allow_void=false
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->postJson("/api/pos/transactions/{$txId}/void", ['reason' => 'Salah input coba void'])
            ->assertStatus(403);
    }

    public function test_owner_per_outlet_customization_overrides_receipt_tax_and_pos_features(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ownerToken = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');
        $owner = User::where('email', 'owner@kopi.id')->firstOrFail();

        // Pick or create a second branch
        $branch = Outlet::where('business_id', $owner->business_id)
            ->where('id', '!=', $owner->outlet_id)
            ->first();
        if (!$branch) {
            $branch = Outlet::create([
                'business_id' => $owner->business_id,
                'name' => 'Outlet Cabang - Dago Bandung',
                'address' => 'Jl. Ir. H. Juanda No. 120',
                'is_active' => true,
            ]);
        }

        $product = Product::where('business_id', $owner->business_id)->firstOrFail();
        Inventory::updateOrCreate(
            ['outlet_id' => $branch->id, 'product_id' => $product->id],
            ['stock_quantity' => 50, 'min_stock_alert' => 5]
        );

        // Customize Branch Tax (5%), Custom Receipt Header & Footer, and override pos_require_shift = false ONLY for this branch
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->putJson("/api/outlets/{$branch->id}", [
                'name' => 'Cabang Dago Spesial',
                'outlet_code' => 'DGO-02',
                'city' => 'Bandung',
                'receipt_header' => 'Promo Happy Hour Dago 14.00-17.00',
                'receipt_footer' => 'Hatur Nuhun - Cabang Dago',
                'tax_percentage' => 5,
                'feature_overrides' => [
                    'pos_require_shift' => false,
                ],
            ])->assertStatus(200);

        // Ensure Cashier at Branch inherits branch override
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->postJson('/api/staff', [
                'name' => 'Kasir Dago',
                'email' => 'kasir.dago@kopi.id',
                'role' => 'cashier',
                'outlet_id' => $branch->id,
                'password' => 'password123',
            ])->assertStatus(201);

        $this->app['auth']->forgetGuards();
        $dagoCashierToken = $this->postJson('/api/login', [
            'email' => 'kasir.dago@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        // /api/me resolves branch override (pos_require_shift=false, tax=5)
        $this->app['auth']->forgetGuards();
        $meDago = $this->withHeader('Authorization', "Bearer {$dagoCashierToken}")
            ->getJson('/api/me')
            ->assertStatus(200);
        $this->assertFalse((bool) $meDago->json('data.business.feature_settings.pos_require_shift'));
        $this->assertEquals(5, (float) $meDago->json('data.outlet.tax_percentage'));

        // Checkout at Branch WITHOUT shift succeeds & applies 5% tax + branch receipt header/footer
        $branchCheckout = $this->withHeader('Authorization', "Bearer {$dagoCashierToken}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $branch->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'amount_paid' => 200000,
            ])->assertStatus(201);
        $this->assertEquals(5, (float) $branchCheckout->json('data.receipt.tax_percentage'));
        $this->assertEquals('Promo Happy Hour Dago 14.00-17.00', $branchCheckout->json('data.receipt.receipt_header'));
        $this->assertEquals('Hatur Nuhun - Cabang Dago', $branchCheckout->json('data.receipt.footer'));

        // Meanwhile Center Outlet cashier STILL requires open shift (pos_require_shift remains true at center)
        $this->app['auth']->forgetGuards();
        $centerToken = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');
        \App\Models\CashierShift::where('outlet_id', $owner->outlet_id)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now()]);
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$centerToken}")
            ->postJson('/api/pos/checkout', [
                'outlet_id' => $owner->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'amount_paid' => 200000,
            ])->assertStatus(422)
              ->assertJsonPath('code', 'SHIFT_NOT_OPEN');
    }

    public function test_payment_receipt_and_midtrans_callback_prevent_tampering_and_double_activation(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['services.midtrans.server_key' => 'test-midtrans-server-key']);

        $clientUser = User::where('email', 'klien@usaha.id')->firstOrFail();
        $superAdmin = User::where('email', 'superadmin@pos.id')->firstOrFail();
        $starter = Plan::where('slug', 'starter')->firstOrFail();

        Sanctum::actingAs($clientUser);
        // Create invoice 1, then replace it with invoice 2 so invoice 1 is marked rejected
        $p1 = $this->postJson('/api/client/rent-template', ['plan_id' => $starter->id])->assertStatus(201)->json('data.payment');
        $p2 = $this->postJson('/api/client/rent-template', ['plan_id' => $starter->id])->assertStatus(201)->json('data.payment');

        // Receipt for pending or rejected invoice MUST be blocked (403)
        $this->getJson("/api/client/payments/{$p1['id']}/receipt")->assertStatus(403);
        $this->getJson("/api/client/payments/{$p2['id']}/receipt")->assertStatus(403);

        // Super Admin verifying rejected invoice 1 MUST fail (422)
        Sanctum::actingAs($superAdmin);
        $this->postJson("/api/admin/subscription-payments/{$p1['id']}/verify")->assertStatus(422);

        $grossAmount = number_format((float) $p2['amount'], 2, '.', '');
        $validSig = hash('sha512', $p2['invoice_number'] . '200' . $grossAmount . 'test-midtrans-server-key');

        // Tampered signature rejected (403)
        $this->postJson('/api/payments/midtrans-callback', [
            'order_id' => $p2['invoice_number'],
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => 'tampered-signature',
            'transaction_status' => 'settlement',
        ])->assertStatus(403);

        // Tampered amount rejected (422)
        $tamperedAmountSig = hash('sha512', $p2['invoice_number'] . '200100.00' . 'test-midtrans-server-key');
        $this->postJson('/api/payments/midtrans-callback', [
            'order_id' => $p2['invoice_number'],
            'status_code' => '200',
            'gross_amount' => '100.00',
            'signature_key' => $tamperedAmountSig,
            'transaction_status' => 'settlement',
        ])->assertStatus(422);

        // Valid Midtrans settlement activates business, generates KW receipt, and is idempotent
        $cb1 = $this->postJson('/api/payments/midtrans-callback', [
            'order_id' => $p2['invoice_number'],
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $validSig,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ])->assertStatus(200);

        $receiptNo = $cb1->json('data.receipt_number');
        $this->assertStringStartsWith('KW-', $receiptNo);
        $firstExpiry = SubscriptionPayment::findOrFail($p2['id'])->billing_expires_at->timestamp;

        // Replay identical Midtrans callback -> idempotent, expiry MUST NOT jump +60 days
        $this->postJson('/api/payments/midtrans-callback', [
            'order_id' => $p2['invoice_number'],
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $validSig,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ])->assertStatus(200);

        $this->assertEquals($firstExpiry, $clientUser->business->fresh()->currentSubscription->expires_at->timestamp);
    }

    public function test_consultation_works_before_and_after_subscription_with_h3_hours_other_sector_and_antispam(): void
    {
        $this->seed(DatabaseSeeder::class);

        $otp = $this->verifyEmailFor('petshop@berkah.id', 'Sari Owner');
        $reg = $this->postJson('/api/register', [
            'business_name' => 'Petshop Berkah',
            'owner_name' => 'Sari Owner',
            'email' => 'petshop@berkah.id',
            'verification_code' => $otp,
            'phone' => '081277778888',
            'password' => 'password123',
        ])->assertStatus(201);

        $token = $reg->json('data.token');
        $owner = User::where('email', 'petshop@berkah.id')->firstOrFail();

        // 1. Date < H+3 MUST fail 422
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Lainnya',
                'business_type_other' => 'Petshop & Grooming',
                'scale' => '1 Outlet',
                'preferred_date' => now()->addDays(1)->format('Y-m-d'),
                'preferred_time' => '10:00',
                'template_gap_notes' => 'Butuh jadwal vaksin dan grooming hewan peliharaan di nota.',
            ])->assertStatus(422)->assertJsonPath('code', 'MIN_H3_REQUIRED');

        // 2. Invalid time outside 09:00–17:00 WIB MUST fail 422
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Lainnya',
                'business_type_other' => 'Petshop & Grooming',
                'scale' => '1 Outlet',
                'preferred_date' => now()->addDays(4)->format('Y-m-d'),
                'preferred_time' => '23:00',
                'template_gap_notes' => 'Butuh jadwal vaksin dan grooming hewan peliharaan di nota.',
            ])->assertStatus(422)->assertJsonPath('code', 'INVALID_CONSULTATION_HOUR');

        // 3. Sector 'Lainnya' without custom description MUST fail 422
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Lainnya',
                'business_type_other' => '',
                'scale' => '1 Outlet',
                'preferred_date' => now()->addDays(4)->format('Y-m-d'),
                'preferred_time' => '14:00',
                'template_gap_notes' => 'Butuh jadwal vaksin dan grooming hewan peliharaan di nota.',
            ])->assertStatus(422);

        // 4. Valid pre-subscription request succeeds -> saves Lainnya + WhatsApp redirect URL
        $c1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Lainnya',
                'business_type_other' => 'Petshop & Grooming',
                'scale' => '1 Outlet',
                'preferred_date' => now()->addDays(4)->format('Y-m-d'),
                'preferred_time' => '14:00',
                'template_gap_notes' => 'Butuh jadwal vaksin dan grooming hewan peliharaan di nota.',
            ])->assertStatus(201);

        $this->assertEquals('Lainnya: Petshop & Grooming', $c1->json('data.business_type'));
        $this->assertEquals('14:00 WIB', $c1->json('data.consultation_time_slot'));
        $this->assertStringContainsString('wa.me/', $c1->json('whatsapp_url'));
        $leadId1 = $c1->json('data.id');

        // 5. Anti-spam: immediate second request blocked (429)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/client/consultations', [
                'business_type' => 'Retail / Minimarket / Grosir',
                'scale' => '1 Outlet',
                'preferred_date' => now()->addDays(5)->format('Y-m-d'),
                'preferred_time' => '10:00',
                'template_gap_notes' => 'Tambahan fitur kedua untuk uji coba batas spam konsultasi.',
            ])->assertStatus(429)->assertJsonPath('code', 'CONSULTATION_RATE_LIMITED');

        // 6. Super Admin resolves #1 & marks WA Sent from SekaYasa + Owner subscribes to Starter
        $superAdmin = User::where('email', 'superadmin@pos.id')->firstOrFail();
        $starter = Plan::where('slug', 'starter')->firstOrFail();
        $owner->business->applySubscriptionPeriod($starter, 30, 'active');

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($superAdmin);
        $this->postJson("/api/admin/consultations/{$leadId1}/whatsapp-status", [
            'whatsapp_status' => 'sent_from_sekayasa',
            'reminder_notes' => 'Sudah dibalas tim SekaYasa via WhatsApp.',
        ])->assertStatus(200)
          ->assertJsonPath('data.whatsapp_status', 'sent_from_sekayasa');

        $overviewAdmin = $this->getJson('/api/admin/platform-overview')->assertStatus(200);
        $row = collect($overviewAdmin->json('data.consultations'))->firstWhere('id', $leadId1);
        $this->assertEquals('sent_from_sekayasa', $row['whatsapp_status']);
        $this->assertNotNull($row['whatsapp_client_url']);
        $this->assertEquals('Sudah dibalas tim SekaYasa via WhatsApp.', $row['reminder_notes']);

        // Backdate first request >24h so Owner can ALSO submit a new feature request AFTER active subscription
        \App\Models\ClientLead::where('id', $leadId1)->update(['created_at' => now()->subHours(26)]);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($owner->fresh());
        $c2 = $this->postJson('/api/client/consultations', [
            'business_type' => 'Lainnya',
            'business_type_other' => 'Petshop & Grooming',
            'scale' => '2-5 Cabang',
            'preferred_date' => now()->addDays(5)->format('Y-m-d'),
            'preferred_time' => '11:00',
            'template_gap_notes' => 'Sudah aktif paket Starter, ingin request tambahan fitur rekap poin member.',
        ])->assertStatus(201);

        $this->assertStringContainsString('Starter', $c2->json('data.subscription_plan_snapshot'));
        $this->assertEquals('template_saas', $owner->business->fresh()->service_type);
    }

    public function test_only_cashier_can_execute_shifts_and_pos_checkout_while_owner_and_admin_are_blocked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@kopi.id')->firstOrFail();
        $admin = User::where('email', 'admin@kopi.id')->firstOrFail();
        $cashier = User::where('email', 'kasir@kopi.id')->firstOrFail();
        $superAdmin = User::where('email', 'superadmin@pos.id')->firstOrFail();
        $product = Product::where('business_id', $cashier->business_id)->firstOrFail();

        // Prepare a clean active cashier shift, held transaction, and paid transaction via legitimate cashier
        \App\Models\CashierShift::where('user_id', $cashier->id)->where('status', 'open')->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        Sanctum::actingAs($cashier);
        $shiftRes = $this->postJson('/api/shifts/open', [
            'outlet_id' => $cashier->outlet_id,
            'initial_cash' => 150000,
        ])->assertStatus(201);
        $shiftId = $shiftRes->json('data.id');

        $holdRes = $this->postJson('/api/pos/hold', [
            'outlet_id' => $cashier->outlet_id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'notes' => 'Hold uji peran',
        ])->assertStatus(200);
        $heldId = $holdRes->json('data.id');

        $txRes = $this->postJson('/api/pos/checkout', [
            'outlet_id' => $cashier->outlet_id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_paid' => 200000,
        ])->assertStatus(201);
        $txId = $txRes->json('data.transaction.id');

        // Non-cashier roles (Owner for preview only, Admin backoffice only, Super Admin monitoring only) are strictly blocked 403
        foreach ([$owner, $admin, $superAdmin] as $nonCashier) {
            $this->app['auth']->forgetGuards();
            Sanctum::actingAs($nonCashier);

            $this->getJson("/api/shifts/current?outlet_id={$cashier->outlet_id}")
                ->assertStatus(200)
                ->assertJsonPath('data', null);

            $this->postJson('/api/shifts/open', [
                'outlet_id' => $cashier->outlet_id,
                'initial_cash' => 100000,
            ])->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');

            $this->postJson("/api/shifts/{$shiftId}/close", [
                'actual_cash' => 150000,
            ])->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');

            $this->postJson('/api/pos/checkout', [
                'outlet_id' => $cashier->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'amount_paid' => 200000,
            ])->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');

            $this->postJson('/api/pos/hold', [
                'outlet_id' => $cashier->outlet_id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');

            $this->deleteJson("/api/pos/held/{$heldId}/resume")
                ->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');

            $this->postJson("/api/pos/transactions/{$txId}/void", [
                'reason' => 'Percobaan void oleh non-kasir',
            ])->assertStatus(403)->assertJsonPath('code', 'CASHIER_ROLE_REQUIRED');
        }

        // Legitimate cashier can resume held order and close shift cleanly
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($cashier);
        $this->deleteJson("/api/pos/held/{$heldId}/resume")->assertStatus(200);
        $this->postJson("/api/shifts/{$shiftId}/close", ['actual_cash' => 200000])->assertStatus(200);
    }

    public function test_kasir_apk_login_rejects_owner_admin_superadmin_and_allows_cashier_with_tandha_branding(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Owner, Admin, and Super Admin MUST be rejected 403 when logging in from Kasir APK context
        foreach (['owner@kopi.id', 'admin@kopi.id', 'superadmin@pos.id'] as $blockedEmail) {
            $res = $this->postJson('/api/login', [
                'email' => $blockedEmail,
                'password' => 'password123',
                'app_context' => 'kasir_apk',
            ]);
            $res->assertStatus(403)
                ->assertJsonPath('code', 'CASHIER_APK_ONLY');
            $this->assertNull($res->json('data.token'));
        }

        // 2. Cashier succeeds with app_context=kasir_apk
        $kasirRes = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
            'app_context' => 'kasir_apk',
        ])->assertStatus(200);
        $this->assertNotEmpty($kasirRes->json('data.token'));
        $this->assertEquals('cashier', $kasirRes->json('data.user.role'));

        // 3. Manifest & Tandha PNG/ICO app icons are properly configured
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertEquals('/pos?app=kasir', $manifest['start_url']);
        $this->assertFileExists(public_path('icon-192.png'));
        $this->assertFileExists(public_path('icon-512.png'));
        $this->assertFileExists(public_path('tandha-icon-192.png'));
        $this->assertFileExists(public_path('tandha-icon-512.png'));
        $this->assertFileExists(public_path('tandha-maskable-192.png'));
        $this->assertFileExists(public_path('tandha-maskable-512.png'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
        $this->assertFileExists(public_path('assets/Logo Tandha.svg'));
    }

    public function test_multi_duration_billing_cycles_and_subscription_gated_apk_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        $trialOwner = User::where('email', 'klien@usaha.id')->firstOrFail();
        $plan = Plan::where('name', 'Starter')->firstOrFail();

        // 1. Unsubscribed trial account (service_type=none) cannot install APK and its cashier is blocked from kasir_apk context
        Sanctum::actingAs($trialOwner);
        $meBefore = $this->getJson('/api/me')->assertStatus(200);
        $this->assertFalse((bool) $meBefore->json('data.business.can_install_apk'));
        $this->assertFalse((bool) $meBefore->json('data.business.has_active_subscription'));

        $dashboardHtml = $this->get('/dashboard')->assertStatus(200)->getContent();
        $this->assertStringNotContainsString('Khusus Pemilik', $dashboardHtml);
        $this->assertStringContainsString('Operasional &amp; Laporan', $dashboardHtml);
        $this->assertStringContainsString('Pengaturan Bisnis', $dashboardHtml);
        $this->assertStringContainsString('Paket &amp; Dukungan', $dashboardHtml);
        $this->assertStringContainsString('hasUnlockedWorkspace', $dashboardHtml);
        $this->assertStringContainsString("installKasirApk('webapk')", $dashboardHtml);
        $this->assertStringContainsString("installKasirApk('apk')", $dashboardHtml);
        $this->assertStringContainsString('window.triggerTandhaInstallApk', $dashboardHtml);
        $this->assertStringContainsString('Panduan Pasang Aplikasi Kasir (WebAPK)', $dashboardHtml);

        $overviewBefore = $this->getJson('/api/client/overview')->assertStatus(200);
        $this->assertFalse((bool) $overviewBefore->json('data.can_install_apk'));
        $this->assertCount(6, $overviewBefore->json('data.billing_periods'));

        $cashierUser = User::create([
            'business_id' => $trialOwner->business_id,
            'outlet_id' => $trialOwner->outlet_id,
            'name' => 'Kasir Trial',
            'email' => 'kasirtrial@usaha.id',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->postJson('/api/login', [
            'email' => 'kasirtrial@usaha.id',
            'password' => 'password123',
            'app_context' => 'kasir_apk',
        ])->assertStatus(403)->assertJsonPath('code', 'SUBSCRIPTION_REQUIRED_FOR_APK');

        // 2. Owner requests 1 Tahun (12 months / 365 days) subscription invoice
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($trialOwner);
        $rentYear = $this->postJson('/api/client/rent-template', [
            'plan_id' => $plan->id,
            'duration_months' => 12,
        ])->assertStatus(201);

        $paymentId = $rentYear->json('data.payment.id');
        $this->assertEquals(12, $rentYear->json('data.payment.duration_months'));
        $this->assertEquals(365, $rentYear->json('data.payment.duration_days'));
        $this->assertEquals((float) $plan->price_monthly * 12, (float) $rentYear->json('data.payment.amount'));

        // Approve and activate subscription
        $payment = SubscriptionPayment::findOrFail($paymentId);
        $payment->approveAndActivate(null, 'manual_test', 'Test activation 12 months');

        $receiptRes = $this->getJson("/api/client/payments/{$paymentId}/receipt")->assertStatus(200);
        $this->assertEquals('1 Tahun', $receiptRes->json('data.duration_label'));
        $this->assertEquals(12, $receiptRes->json('data.duration_months'));

        $meAfter = $this->getJson('/api/me')->assertStatus(200);
        $this->assertTrue((bool) $meAfter->json('data.business.can_install_apk'));
        $this->assertTrue((bool) $meAfter->json('data.business.has_active_subscription'));

        // Now cashier on subscribed business can login in kasir_apk mode
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', [
            'email' => 'kasirtrial@usaha.id',
            'password' => 'password123',
            'app_context' => 'kasir_apk',
        ])->assertStatus(200)->assertJsonPath('data.user.email', $cashierUser->email);
    }

    public function test_security_hardening_tenant_idor_financial_clamp_otp_lockout_and_headers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $kopiOwner = User::where('email', 'owner@kopi.id')->firstOrFail();
        $kopiCashier = User::where('email', 'kasir@kopi.id')->firstOrFail();
        $trialOwner = User::where('email', 'klien@usaha.id')->firstOrFail();
        $kopiOutlet = Outlet::where('business_id', $kopiOwner->business_id)->firstOrFail();
        $kopiProduct = Product::where('business_id', $kopiOwner->business_id)->firstOrFail();

        // 1. Security headers present on web & API routes
        $homeRes = $this->get('/')->assertStatus(200);
        $homeRes->assertHeader('X-Content-Type-Options', 'nosniff');
        $homeRes->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $clientRouteRes = $this->get('/client')->assertStatus(200);
        $clientRouteRes->assertSee('Langganan');

        // 2. Cashier checkout clamps >100% percent discount and enforces server tax_percentage
        Sanctum::actingAs($kopiCashier);
        CashierShift::create([
            'business_id' => $kopiCashier->business_id,
            'outlet_id' => $kopiOutlet->id,
            'user_id' => $kopiCashier->id,
            'initial_cash' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $checkoutRes = $this->postJson('/api/pos/checkout', [
            'outlet_id' => $kopiOutlet->id,
            'items' => [['product_id' => $kopiProduct->id, 'quantity' => 1]],
            'discount_type' => 'percent',
            'discount_amount' => 500, // Attempt >100% negative total exploit
            'tax_percentage' => 0,    // Attempt zero-tax client bypass
            'payment_method' => 'cash',
            'amount_paid' => 50000,
        ])->assertStatus(201);

        $txId = $checkoutRes->json('data.transaction.id');
        $this->assertGreaterThanOrEqual(0, (float) $checkoutRes->json('data.transaction.total_amount'));
        $this->assertEquals(
            (float) $kopiOutlet->effectiveTaxPercentage(Business::findOrFail($kopiOwner->business_id)),
            (float) $checkoutRes->json('data.receipt.tax_percentage')
        );

        // Role isolation: Cashier denied inventory & sales report & foreign outlet dashboard endpoints
        $this->getJson('/api/inventory')->assertStatus(403);
        $this->getJson('/api/reports/sales')->assertStatus(403);
        $otherOutlet = Outlet::where('business_id', $trialOwner->business_id)->firstOrFail();
        $this->getJson("/api/reports/dashboard?outlet_id={$otherOutlet->id}")->assertStatus(404);

        // 3. Cross-tenant IDOR protection: other business cannot access transaction detail or pass foreign outlet_id
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($trialOwner);
        $this->getJson("/api/pos/transactions/{$txId}")->assertStatus(404);
        $this->getJson("/api/pos/history?outlet_id={$kopiOutlet->id}")->assertStatus(404);
        $this->getJson("/api/products?outlet_id={$kopiOutlet->id}")->assertStatus(404);
        $this->getJson("/api/reports/dashboard?outlet_id={$kopiOutlet->id}")->assertStatus(404);
        $this->getJson("/api/inventory?outlet_id={$kopiOutlet->id}")->assertStatus(404);

        // 4. OTP brute-force lockout after 5 invalid attempts invalidates the valid OTP (guest session)
        $this->app['auth']->forgetGuards();
        $realOtp = $this->verifyEmailFor('locked@bisnisbaru.id', 'Bima');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'business_name' => 'Toko Test Lockout',
                'legal_type' => 'Perorangan',
                'business_category' => 'Retail / Minimarket / Grosir',
                'owner_name' => 'Bima',
                'nik' => '3578012345678999',
                'email' => 'locked@bisnisbaru.id',
                'verification_code' => '000000',
                'phone' => '081299998888',
                'outlet_name' => 'Outlet Lock',
                'address' => 'Jl. Test No. 1',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'timezone' => 'Asia/Jakarta',
                'tax_percentage' => 11,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertStatus(422);
        }

        // Now even the real OTP must be invalidated and rejected
        $this->postJson('/api/register', [
            'business_name' => 'Toko Test Lockout',
            'legal_type' => 'Perorangan',
            'business_category' => 'Retail / Minimarket / Grosir',
            'owner_name' => 'Bima',
            'nik' => '3578012345678999',
            'email' => 'locked@bisnisbaru.id',
            'verification_code' => $realOtp,
            'phone' => '081299998888',
            'outlet_name' => 'Outlet Lock',
            'address' => 'Jl. Test No. 1',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_otp_endpoints_return_structured_json_error_when_mail_transport_fails(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('role', 'owner')->where('is_active', true)->firstOrFail();

        $pendingMail = new class {
            public function send($mailable): void
            {
                throw new \RuntimeException('Connection could not be established with host smtp.gmail.com');
            }
        };

        Mail::shouldReceive('to')->andReturn($pendingMail);

        $this->postJson('/api/email/send-verification', [
            'email' => 'calon@bisnisbaru.id',
            'owner_name' => 'Calon Owner',
        ])->assertStatus(500)
          ->assertJson([
              'status' => 'error',
          ]);

        $this->postJson('/api/password/forgot', [
            'email' => $owner->email,
        ])->assertStatus(500)
          ->assertJson([
              'status' => 'error',
          ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => strtolower($owner->email),
        ]);
    }

    public function test_tandha_shield_antibot_blocks_bots_honeypot_replay_and_verifies_humans(): void
    {
        $this->seed(DatabaseSeeder::class);

        config([
            'services.antibot.enabled' => true,
            'services.antibot.driver' => 'tandha',
        ]);

        // 1. Direct bot POST without completing Tandha Shield -> blocked (422)
        $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);

        // 2. Honeypot trap filled -> blocked (422)
        $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
            'company_website' => 'https://spam-bot.invalid',
            'cf_turnstile_response' => 'tsh_forged',
        ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);

        // 3. Issue real visual GD puzzle challenge: target_x must NOT leak in payload
        $challengeRes = $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/antibot/challenge', ['context' => 'login'])
            ->assertStatus(200);

        $challenge = $challengeRes->json('data');
        $this->assertArrayNotHasKey('target_x', $challenge);
        $this->assertStringStartsWith('data:image/png;base64,', $challenge['bg_image']);
        $this->assertStringStartsWith('data:image/png;base64,', $challenge['piece_image']);

        $cachedTargetX = (int) (\Illuminate\Support\Facades\Cache::get('antibot:challenge:' . $challenge['challenge_id'])['target_x'] ?? -1);
        $this->assertGreaterThanOrEqual(230, $cachedTargetX);

        // Wrong x coordinate fails (422) AND burns the challenge
        usleep(180000);
        $wrongX = $cachedTargetX > 300 ? 40 : 500;
        $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/antibot/verify', [
                'challenge_id' => $challenge['challenge_id'],
                'x' => $wrongX,
                'drag_points' => 8,
                'duration_ms' => 300,
                'input_method' => 'pointer',
            ])->assertStatus(422);

        // Replaying burned challenge even with correct x must fail (422)
        $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/antibot/verify', [
                'challenge_id' => $challenge['challenge_id'],
                'x' => $cachedTargetX,
                'drag_points' => 10,
                'duration_ms' => 350,
                'input_method' => 'pointer',
            ])->assertStatus(422);

        // 4. Fresh challenge solved with matching puzzle x returns single-use token
        $challenge2 = $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/antibot/challenge', ['context' => 'login'])
            ->assertStatus(200)
            ->json('data');
        $targetX2 = (int) (\Illuminate\Support\Facades\Cache::get('antibot:challenge:' . $challenge2['challenge_id'])['target_x'] ?? -1);

        usleep(200000); // 200ms human swipe dwell

        $token = $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/antibot/verify', [
                'challenge_id' => $challenge2['challenge_id'],
                'x' => $targetX2,
                'drag_points' => 12,
                'duration_ms' => 420,
                'input_method' => 'pointer',
            ])->assertStatus(200)
            ->json('data.verification_token');

        // 5. Valid token allows login (200), then replaying exact same token fails (422)
        $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/login', [
                'email' => 'kasir@kopi.id',
                'password' => 'password123',
                'cf_turnstile_response' => $token,
            ])->assertStatus(200);

        $this->withHeaders(['User-Agent' => 'PHPUnitBrowser/1.0'])
            ->postJson('/api/login', [
                'email' => 'kasir@kopi.id',
                'password' => 'password123',
                'cf_turnstile_response' => $token,
            ])->assertStatus(422)->assertJsonValidationErrors(['cf_turnstile_response']);
    }

    public function test_cashier_token_never_expires_while_non_cashier_expires_and_revokes_on_deactivation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cashierLogin = $this->postJson('/api/login', [
            'email' => 'kasir@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200);
        $cashierToken = $cashierLogin->json('data.token');

        $ownerLogin = $this->postJson('/api/login', [
            'email' => 'owner@kopi.id',
            'password' => 'password123',
        ])->assertStatus(200);
        $ownerToken = $ownerLogin->json('data.token');

        $cashierUser = User::where('email', 'kasir@kopi.id')->firstOrFail();
        $ownerUser = User::where('email', 'owner@kopi.id')->firstOrFail();

        $cashierPat = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $cashierUser->id)->latest('id')->firstOrFail();
        $ownerPat = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $ownerUser->id)->latest('id')->firstOrFail();

        // Cashier tokens have null expires_at (permanent); Owner tokens have finite expiration
        $this->assertNull($cashierPat->expires_at);
        $this->assertNotNull($ownerPat->expires_at);

        // Simulate 180 days passed: Cashier remains authenticated; Owner expires cleanly (401)
        \Illuminate\Support\Facades\DB::table('personal_access_tokens')
            ->where('id', $cashierPat->id)
            ->update(['created_at' => now()->subDays(180)]);
        \Illuminate\Support\Facades\DB::table('personal_access_tokens')
            ->where('id', $ownerPat->id)
            ->update([
                'created_at' => now()->subDays(180),
                'expires_at' => now()->subDays(173),
            ]);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'kasir@kopi.id');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->getJson('/api/me')
            ->assertStatus(401);

        // Revoking / deactivating cashier immediately invalidates the persistent token
        Sanctum::actingAs($ownerUser);
        $this->putJson("/api/staff/{$cashierUser->id}", [
            'is_active' => false,
        ])->assertStatus(200);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$cashierToken}")
            ->getJson('/api/me')
            ->assertStatus(401);
    }
}
