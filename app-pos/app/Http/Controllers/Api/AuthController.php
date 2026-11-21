<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new Business + Owner (FR-001)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Create Business (starts with service_type = 'none' until client chooses Rent Template or Custom Consultation)
            $business = Business::create([
                'name' => $validated['business_name'],
                'owner_name' => $validated['owner_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'tax_percentage' => 11.00,
                'receipt_footer' => 'Terima kasih telah berbelanja!',
                'is_active' => true,
                'service_type' => 'none',
            ]);

            // 2. Default Starter Subscription (Trial 14 days)
            $starterPlan = Plan::where('slug', 'starter')->first();
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $starterPlan ? $starterPlan->id : 1,
                'status' => 'trial',
                'starts_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);

            // 3. Create Default Outlet (FR-007)
            $outlet = Outlet::create([
                'business_id' => $business->id,
                'name' => 'Outlet Utama',
                'address' => 'Pusat',
                'is_active' => true,
            ]);

            // 4. Create Owner User
            $user = User::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'owner',
                'is_active' => true,
            ]);

            $token = $user->createToken('pos-auth-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi bisnis berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $user->load('business', 'outlet'),
                ],
            ], 201);
        });
    }

    /**
     * User Login (FR-002)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang dimasukkan tidak cocok dengan catatan kami.'],
            ]);
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

        $token = $user->createToken('pos-auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => $user->load('business.currentSubscription.plan', 'outlet', 'activeShift'),
            ],
        ]);
    }

    /**
     * Get Current Profile (FR-005)
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('business.outlets', 'business.currentSubscription.plan', 'outlet', 'activeShift'),
        ]);
    }

    /**
     * Logout (FR-005)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }
}
