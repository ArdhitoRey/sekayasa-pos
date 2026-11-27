<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AuthOtpMail;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\TurnstileService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Normalize Indonesian phone numbers (e.g. +62812... / 62812... -> 0812...)
     * so duplicate registrations cannot bypass uniqueness with formatting variations.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Issue role-scoped Sanctum token: permanent (non-expiring) for Cashier POS terminals, finite lifetime for Owner/Admin/SuperAdmin.
     */
    private function createRoleScopedToken(User $user, string $tokenName = 'pos-auth-token'): string
    {
        $expiresAt = $user->role === 'cashier'
            ? null
            : now()->addMinutes(max(60, (int) config('sanctum.non_cashier_expiration_minutes', 10080)));

        return $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
    }

    /**
     * Send an OTP email with structured transport error handling so SMTP failures never crash with raw HTML.
     */
    private function sendOtpMailSafely(string $recipientEmail, AuthOtpMail $mailable, string $context): ?JsonResponse
    {
        try {
            Mail::to($recipientEmail)->send($mailable);
            return null;
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email OTP (' . $context . ')', [
                'email' => $recipientEmail,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.' . config('mail.default') . '.host'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim kode OTP ke email melalui server mail. Pastikan konfigurasi SMTP (MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS) di .env sudah valid.',
            ], 500);
        }
    }

    /**
     * Attach local diagnostic notice when MAIL_MAILER is still set to log/array so testers know emails aren't sent to Gmail.
     */
    private function appendMailModeMetadata(array $payload): array
    {
        $mailer = (string) config('mail.default');
        if (
            !app()->runningUnitTests()
            && app()->environment('local')
            && config('app.debug')
            && in_array($mailer, ['log', 'array'], true)
        ) {
            $payload['mail_mode'] = $mailer;
            $payload['mail_notice'] = 'Mode MAIL_MAILER=' . $mailer . ' masih aktif di file .env, sehingga kode OTP belum dikirim ke kotak masuk Gmail melainkan dicatat ke storage/logs/laravel.log. Atur MAIL_MAILER=smtp dengan akun SMTP / Gmail App Password di .env untuk pengiriman email nyata.';
        }

        return $payload;
    }

    /**
     * Step 0 of Registration: Send 6-Digit Email Verification OTP so every registered email is confirmed real & usable for Forgot Password.
     */
    public function sendRegistrationVerification(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email|unique:businesses,email',
            'owner_name' => 'nullable|string|max:255',
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk melalui tab Masuk Akun atau gunakan Lupa Kata Sandi.',
        ]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('email_verify:' . $validated['email'], Hash::make($code), now()->addMinutes(15));

        if ($mailError = $this->sendOtpMailSafely(
            $validated['email'],
            new AuthOtpMail($code, 'verify_email', $validated['owner_name'] ?? 'Calon Mitra'),
            'verify_email'
        )) {
            Cache::forget('email_verify:' . $validated['email']);
            return $mailError;
        }

        ActivityLog::record(
            null,
            null,
            'email_verification_sent',
            'User',
            null,
            ['email' => $validated['email']],
            $request->ip()
        );

        return response()->json($this->appendMailModeMetadata([
            'status' => 'success',
            'message' => 'Kode verifikasi 6 digit telah dikirim ke email ' . $validated['email'] . '. Periksa kotak masuk atau folder spam Anda.',
        ]));
    }

    /**
     * Register a new Business + Primary Outlet + Owner (FR-001)
     * Enforces email verification (OTP or verified Google profile), 1 user = 1 account, and full business data.
     */
    public function register(Request $request, TurnstileService $turnstile)
    {
        if ($request->user('sanctum')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah terdaftar dan sedang login. Satu user tidak dapat mendaftar akun lagi.',
            ], 403);
        }

        $turnstile->verifyOrFail(
            $request->input('cf_turnstile_response'),
            $request->ip(),
            $request->userAgent(),
            $request->input('company_website')
        );

        $normalizedEmail = strtolower(trim((string) $request->input('email', '')));
        $normalizedPhone = $this->normalizePhone($request->input('phone'));
        $normalizedNik = $request->filled('nik') ? preg_replace('/\D+/', '', (string) $request->input('nik')) : null;
        $googlePendingToken = trim((string) $request->input('google_pending_token', ''));

        $request->merge([
            'email' => $normalizedEmail,
            'nik' => $normalizedNik,
        ]);

        $googleProfile = null;
        if ($googlePendingToken !== '') {
            $googleProfile = Cache::get('google_pending:' . $googlePendingToken);
            if (!$googleProfile || strtolower($googleProfile['email']) !== $normalizedEmail) {
                throw ValidationException::withMessages([
                    'email' => ['Sesi Google tidak valid atau email tidak cocok dengan akun Google Anda.'],
                ]);
            }
        }

        $passwordRule = $googleProfile
            ? ($request->filled('password') ? 'nullable|string|min:6|confirmed' : 'nullable|string')
            : ($request->has('password_confirmation') ? 'required|string|min:6|confirmed' : 'required|string|min:6');

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'legal_type' => 'nullable|string|max:50',
            'business_category' => 'nullable|string|max:100',
            'owner_name' => 'required|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'nik' => 'nullable|digits:16|unique:users,nik',
            'email' => 'required|email|unique:users,email|unique:businesses,email',
            'phone' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:30',
            'nib' => 'nullable|string|max:30',
            'outlet_name' => 'nullable|string|max:255',
            'outlet_code' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'outlet_count_estimate' => 'nullable|string|max:50',
            'timezone' => 'nullable|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'verification_code' => $googleProfile ? 'nullable|string' : 'required|digits:6',
            'password' => $passwordRule,
        ], [
            'email.unique' => 'Email ini sudah pernah terdaftar. Satu user hanya dapat mendaftar satu kali, silakan masuk melalui tab Masuk Akun.',
            'nik.unique' => 'NIK ini sudah terdaftar pada akun lain. Satu pengguna hanya diperbolehkan memiliki satu akun.',
            'nik.digits' => 'NIK KTP harus terdiri dari tepat 16 digit angka.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'verification_code.required' => 'Kode verifikasi email 6 digit wajib diisi.',
            'verification_code.digits' => 'Kode verifikasi email harus 6 digit angka.',
        ]);

        if (!$googleProfile) {
            $attemptsKey = 'email_verify_attempts:' . $normalizedEmail;
            $cachedOtpHash = Cache::get('email_verify:' . $normalizedEmail);
            if (!$cachedOtpHash || !Hash::check((string) $validated['verification_code'], $cachedOtpHash)) {
                $attempts = (int) Cache::increment($attemptsKey);
                Cache::put($attemptsKey, $attempts, now()->addMinutes(15));
                if ($attempts >= 5) {
                    Cache::forget('email_verify:' . $normalizedEmail);
                }
                throw ValidationException::withMessages([
                    'verification_code' => [$attempts >= 5
                        ? 'Terlalu banyak percobaan kode verifikasi. Silakan kirim ulang kode baru.'
                        : 'Kode verifikasi email tidak cocok atau sudah kedaluwarsa.'],
                ]);
            }
            Cache::forget($attemptsKey);
        }

        if ($normalizedPhone) {
            $phoneVariants = array_unique([
                $normalizedPhone,
                trim((string) $request->input('phone')),
                '62' . ltrim($normalizedPhone, '0'),
                '+62' . ltrim($normalizedPhone, '0'),
            ]);

            $phoneExists = User::whereIn('phone', $phoneVariants)->exists()
                || Business::whereIn('phone', $phoneVariants)->exists();

            if ($phoneExists) {
                throw ValidationException::withMessages([
                    'phone' => ['Nomor telepon / WhatsApp ini sudah pernah terdaftar pada akun lain. Satu user tidak dapat mendaftar lebih dari satu kali.'],
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $normalizedPhone, $normalizedNik, $googleProfile, $googlePendingToken, $normalizedEmail) {
            $address = !empty($validated['address']) ? trim($validated['address']) : null;
            $city = !empty($validated['city']) ? trim($validated['city']) : null;
            $outletName = !empty($validated['outlet_name']) ? trim($validated['outlet_name']) : 'Outlet Utama';

            // 1. Create Business (starts with service_type = 'none' until client chooses Rent Template or Custom Consultation)
            $business = Business::create([
                'name' => trim($validated['business_name']),
                'legal_type' => $validated['legal_type'] ?? null,
                'business_category' => $validated['business_category'] ?? null,
                'owner_name' => trim($validated['owner_name']),
                'email' => $validated['email'],
                'phone' => $normalizedPhone,
                'npwp' => !empty($validated['npwp']) ? trim($validated['npwp']) : null,
                'nib' => !empty($validated['nib']) ? trim($validated['nib']) : null,
                'address' => $address,
                'city' => $city,
                'province' => !empty($validated['province']) ? trim($validated['province']) : null,
                'postal_code' => !empty($validated['postal_code']) ? trim($validated['postal_code']) : null,
                'outlet_count_estimate' => $validated['outlet_count_estimate'] ?? null,
                'currency' => 'IDR',
                'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
                'tax_percentage' => $validated['tax_percentage'] ?? 11.00,
                'receipt_footer' => 'Terima kasih telah berbelanja!',
                'is_active' => true,
                'service_type' => 'none',
            ]);

            // 2. Create Primary Outlet tied to this Business (FR-007)
            $outlet = Outlet::create([
                'business_id' => $business->id,
                'name' => $outletName,
                'outlet_code' => !empty($validated['outlet_code']) ? strtoupper(trim($validated['outlet_code'])) : 'OUT-01',
                'address' => $address ?? 'Pusat',
                'city' => $city,
                'phone' => $normalizedPhone,
                'is_active' => true,
            ]);

            // 4. Create Owner User tied to Business & Primary Outlet
            $rawPassword = !empty($validated['password']) ? $validated['password'] : Str::random(24);
            $user = User::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'name' => trim($validated['owner_name']),
                'email' => $validated['email'],
                'google_id' => $googleProfile['google_id'] ?? null,
                'avatar' => $googleProfile['avatar'] ?? null,
                'email_verified_at' => now(),
                'phone' => $normalizedPhone,
                'nik' => $normalizedNik,
                'job_title' => $validated['job_title'] ?? 'Pemilik Usaha (Owner)',
                'password' => Hash::make($rawPassword),
                'role' => 'owner',
                'is_active' => true,
            ]);

            if ($googleProfile && $googlePendingToken !== '') {
                Cache::forget('google_pending:' . $googlePendingToken);
            } else {
                Cache::forget('email_verify:' . $normalizedEmail);
            }

            $token = $this->createRoleScopedToken($user, 'pos-auth-token');

            ActivityLog::record(
                $business->id,
                $user->id,
                $googleProfile ? 'register_business_google' : 'register_business',
                'Business',
                $business->id,
                ['business_name' => $business->name, 'category' => $business->business_category, 'email_verified' => true],
                request()->ip()
            );

            $user->load('business', 'outlet');
            if ($user->business) {
                $user->business->setAttribute('subscription_summary', $user->business->subscriptionSummary());
                $user->business->setAttribute('can_install_apk', false);
                $user->business->setAttribute('has_active_subscription', false);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi bisnis berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                ],
            ], 201);
        });
    }

    /**
     * User Login (FR-002)
     */
    public function login(Request $request, TurnstileService $turnstile)
    {
        $turnstile->verifyOrFail(
            $request->input('cf_turnstile_response'),
            $request->ip(),
            $request->userAgent(),
            $request->input('company_website')
        );

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'app_context' => 'nullable|in:web,kasir_apk',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            ActivityLog::record(
                $user?->business_id,
                $user?->id,
                'login_failed',
                'User',
                $user?->id,
                ['email' => $request->email],
                $request->ip()
            );

            throw ValidationException::withMessages([
                'email' => ['Kredensial yang dimasukkan tidak cocok dengan catatan kami.'],
            ]);
        }

        if ($request->input('app_context') === 'kasir_apk') {
            if ($user->role !== 'cashier') {
                ActivityLog::record(
                    $user->business_id,
                    $user->id,
                    'login_blocked_apk_non_cashier',
                    'User',
                    $user->id,
                    ['email' => $user->email, 'role' => $user->role],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'error',
                    'code' => 'CASHIER_APK_ONLY',
                    'message' => 'Aplikasi Kasir hanya dapat diakses oleh akun Kasir (Cashier). Akun Owner dan Admin silakan masuk melalui website portal.',
                ], 403);
            }

            if ($user->business && !$user->business->hasSubscribedAppAccess()) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'SUBSCRIPTION_REQUIRED_FOR_APK',
                    'message' => 'Aplikasi Kasir belum aktif. Silakan aktifkan paket langganan terlebih dahulu melalui akun Pemilik Bisnis.',
                ], 403);
            }
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi pemilik bisnis.',
            ], 403);
        }

        // Check business active
        if ($user->business && !$user->business->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun bisnis dinonaktifkan.',
            ], 403);
        }

        $token = $this->createRoleScopedToken($user, 'pos-auth-token');

        ActivityLog::record(
            $user->business_id,
            $user->id,
            'login_success',
            'User',
            $user->id,
            ['role' => $user->role],
            $request->ip()
        );

        $user->load('business.outlets', 'business.currentSubscription.plan', 'outlet', 'activeShift');
        if ($user->business) {
            $hasSubAccess = $user->business->hasSubscribedAppAccess();
            $user->business->setAttribute('subscription_summary', $user->business->subscriptionSummary());
            $user->business->setAttribute('can_install_apk', $hasSubAccess);
            $user->business->setAttribute('has_active_subscription', $hasSubAccess);
            $user->business->setAttribute('feature_settings', $user->business->resolvedFeatureSettings($user->outlet));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ]);
    }

    /**
     * Get Current Profile (FR-005)
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('business.outlets', 'business.currentSubscription.plan', 'outlet', 'activeShift');
        if ($user->business) {
            $hasSubAccess = $user->business->hasSubscribedAppAccess();
            $user->business->setAttribute('subscription_summary', $user->business->subscriptionSummary());
            $user->business->setAttribute('can_install_apk', $hasSubAccess);
            $user->business->setAttribute('has_active_subscription', $hasSubAccess);
            $user->business->setAttribute('plan_limits', $user->business->planLimits());
            $user->business->setAttribute('feature_settings', $user->business->resolvedFeatureSettings($user->outlet));
            $user->business->setAttribute('usage', [
                'outlets_count' => $user->business->outlets()->count(),
                'users_count' => $user->business->users()->count(),
                'products_count' => $user->business->products()->where('is_active', true)->count(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    /**
     * Issue a server-backed Tandha Shield Anti-Bot challenge.
     */
    public function createAntibotChallenge(Request $request, TurnstileService $turnstile)
    {
        $validated = $request->validate([
            'context' => 'nullable|in:login,register',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $turnstile->issueChallenge(
                $request->ip(),
                $request->userAgent(),
                $validated['context'] ?? 'login'
            ),
        ]);
    }

    /**
     * Verify visual Tandha Shield puzzle telemetry and return a single-use token.
     */
    public function verifyAntibotChallenge(Request $request, TurnstileService $turnstile)
    {
        $validated = $request->validate([
            'challenge_id' => 'required|string|max:64',
            'x' => 'required|integer|min:0|max:' . TurnstileService::IMG_WIDTH,
            'drag_points' => 'nullable|integer|min:0|max:5000',
            'key_steps' => 'nullable|integer|min:0|max:500',
            'duration_ms' => 'required|integer|min:0|max:600000',
            'input_method' => 'nullable|in:pointer,keyboard',
            'company_website' => 'nullable|string|max:255',
        ]);

        $token = $turnstile->verifyChallenge(
            $validated['challenge_id'],
            [
                'x' => (int) $validated['x'],
                'drag_points' => (int) ($validated['drag_points'] ?? 0),
                'key_steps' => (int) ($validated['key_steps'] ?? 0),
                'duration_ms' => (int) $validated['duration_ms'],
                'input_method' => $validated['input_method'] ?? 'pointer',
                'honeypot' => $validated['company_website'] ?? null,
            ],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'verification_token' => $token,
            ],
        ]);
    }

    /**
     * Request Password Reset 6-Digit Code via Registered Email.
     */
    public function forgotPassword(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if ($user && $user->is_active) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            if ($mailError = $this->sendOtpMailSafely(
                $user->email,
                new AuthOtpMail($code, 'reset_password', $user->name),
                'reset_password'
            )) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                return $mailError;
            }

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'password_reset_requested',
                'User',
                $user->id,
                ['email' => $user->email],
                $request->ip()
            );
        }

        return response()->json($this->appendMailModeMetadata([
            'status' => 'success',
            'message' => 'Jika email terdaftar dan aktif, kode reset kata sandi 6 digit telah dikirim ke email tersebut.',
        ]));
    }

    /**
     * Confirm 6-Digit Reset Code and Save New Password.
     */
    public function resetPassword(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $attemptsKey = 'password_reset_attempts:' . $validated['email'];
        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();
        $isExpired = !$record || !$record->created_at || Carbon::parse($record->created_at)->addMinutes(15)->isPast();

        if ($isExpired || !Hash::check((string) $validated['code'], $record->token)) {
            $attempts = (int) Cache::increment($attemptsKey);
            Cache::put($attemptsKey, $attempts, now()->addMinutes(15));
            if ($attempts >= 5 && $record) {
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            }
            throw ValidationException::withMessages([
                'code' => [$attempts >= 5
                    ? 'Terlalu banyak percobaan kode salah. Silakan minta ulang kode reset.'
                    : 'Kode reset kata sandi tidak valid atau sudah kedaluwarsa.'],
            ]);
        }
        Cache::forget($attemptsKey);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        ActivityLog::record(
            $user->business_id,
            $user->id,
            'password_reset_success',
            'User',
            $user->id,
            ['email' => $user->email],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.',
        ]);
    }

    /**
     * Redirect Browser to Google OAuth 2.0 Consent Screen.
     */
    public function redirectToGoogle(Request $request)
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect('/login?google_error=not_configured');
        }

        $state = Str::random(40);
        Cache::put('google_oauth_state:' . $state, true, now()->addMinutes(10));

        $redirectUri = url(config('services.google.redirect', '/auth/google/callback'));
        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle Google OAuth Callback: log in existing registered users, OR force new Google users through full business registration.
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect('/login?google_error=access_denied');
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($state === '' || !Cache::pull('google_oauth_state:' . $state) || $code === '') {
            return redirect('/login?google_error=invalid_state');
        }

        $redirectUri = url(config('services.google.redirect', '/auth/google/callback'));
        $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (!$tokenRes->ok() || empty($tokenRes->json('access_token'))) {
            return redirect('/login?google_error=token_exchange_failed');
        }

        $profileRes = Http::withToken($tokenRes->json('access_token'))
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (!$profileRes->ok() || empty($profileRes->json('email'))) {
            return redirect('/login?google_error=profile_failed');
        }

        $googleEmail = strtolower(trim((string) $profileRes->json('email')));
        $googleId = (string) $profileRes->json('sub');
        $googleName = (string) ($profileRes->json('name') ?? 'Pemilik Usaha');
        $googleAvatar = $profileRes->json('picture');

        $existingUser = User::where('google_id', $googleId)
            ->orWhere('email', $googleEmail)
            ->first();

        if ($existingUser) {
            if (!$existingUser->is_active || ($existingUser->business && !$existingUser->business->is_active)) {
                return redirect('/login?google_error=account_suspended');
            }

            $existingUser->update([
                'google_id' => $googleId,
                'avatar' => $googleAvatar ?? $existingUser->avatar,
                'email_verified_at' => $existingUser->email_verified_at ?? now(),
            ]);

            $token = $this->createRoleScopedToken($existingUser, 'pos-google-token');
            $existingUser->load('business.outlets', 'business.currentSubscription.plan', 'outlet', 'activeShift');
            if ($existingUser->business) {
                $existingUser->business->setAttribute('subscription_summary', $existingUser->business->subscriptionSummary());
                $existingUser->business->setAttribute('feature_settings', $existingUser->business->resolvedFeatureSettings($existingUser->outlet));
            }

            ActivityLog::record(
                $existingUser->business_id,
                $existingUser->id,
                'login_google',
                'User',
                $existingUser->id,
                ['email' => $existingUser->email, 'role' => $existingUser->role],
                $request->ip()
            );

            $exchangeNonce = Str::random(40);
            Cache::put('google_exchange:' . $exchangeNonce, [
                'type' => 'login',
                'token' => $token,
                'user' => $existingUser,
            ], now()->addMinutes(3));

            return redirect('/login?google_exchange=' . $exchangeNonce);
        }

        // New Google user -> MUST still fill all business registration fields (no skipping!)
        $pendingNonce = Str::random(40);
        Cache::put('google_pending:' . $pendingNonce, [
            'google_id' => $googleId,
            'email' => $googleEmail,
            'name' => $googleName,
            'avatar' => $googleAvatar,
        ], now()->addMinutes(20));

        return redirect('/login?tab=register&google_pending=' . $pendingNonce);
    }

    /**
     * Exchange One-Time Nonce for Authenticated Google Session or Pre-filled Pending Registration Profile.
     */
    public function exchangeGoogleToken(string $nonce)
    {
        if ($loginData = Cache::pull('google_exchange:' . $nonce)) {
            return response()->json([
                'status' => 'success',
                'data' => $loginData,
            ]);
        }

        if ($pending = Cache::get('google_pending:' . $nonce)) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'type' => 'pending_registration',
                    'google_pending_token' => $nonce,
                    'email' => $pending['email'],
                    'name' => $pending['name'],
                    'avatar' => $pending['avatar'] ?? null,
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Sesi login Google tidak ditemukan atau telah kedaluwarsa.',
        ], 404);
    }

    /**
     * Logout (FR-005)
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        ActivityLog::record(
            $user?->business_id,
            $user?->id,
            'logout',
            'User',
            $user?->id,
            ['role' => $user?->role],
            $request->ip()
        );

        $user?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }
}
