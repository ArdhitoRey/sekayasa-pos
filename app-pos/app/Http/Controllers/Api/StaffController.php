<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    private function ensureOwner(Request $request): User
    {
        $user = $request->user();
        if (!$user || !$user->canManageBusiness() || !$user->business) {
            abort(403, 'Hanya Pemilik Bisnis (Owner) yang berhak mengelola akun staf dan profil bisnis.');
        }

        return $user;
    }

    /**
     * List business users/staff along with plan user/outlet quotas.
     */
    public function index(Request $request)
    {
        $owner = $this->ensureOwner($request);
        $business = $owner->business()->with('currentSubscription.plan', 'outlets')->firstOrFail();
        $limits = $business->planLimits();

        $members = User::where('business_id', $business->id)
            ->with('outlet:id,name')
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 WHEN role = 'admin' THEN 1 ELSE 2 END")
            ->latest('id')
            ->get(['id', 'business_id', 'outlet_id', 'name', 'email', 'phone', 'job_title', 'role', 'is_active', 'email_verified_at', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'members' => $members,
                'outlets' => $business->outlets()->where('is_active', true)->get(['id', 'name', 'outlet_code']),
                'limits' => [
                    'plan_name' => $limits['plan_name'],
                    'max_users' => $limits['max_users'],
                    'used_users' => $members->count(),
                    'remaining_users' => max(0, $limits['max_users'] - $members->count()),
                    'max_outlets' => $limits['max_outlets'],
                    'used_outlets' => $business->outlets()->where('is_active', true)->count(),
                    'has_admin' => $members->where('role', 'admin')->where('is_active', true)->isNotEmpty(),
                ],
            ],
        ]);
    }

    /**
     * Owner adds a new Staff Member (Admin [optional] or Kasir) within Plan User Quota.
     */
    public function store(Request $request)
    {
        $owner = $this->ensureOwner($request);

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:25',
            'job_title' => 'nullable|string|max:100',
            'role' => 'required|in:admin,cashier',
            'outlet_id' => 'required|integer|exists:outlets,id',
            'password' => 'required|string|min:6',
        ], [
            'email.unique' => 'Email ini sudah digunakan akun lain.',
            'role.in' => 'Role staf hanya boleh admin atau cashier.',
        ]);

        Outlet::where('business_id', $owner->business_id)->findOrFail($validated['outlet_id']);

        return DB::transaction(function () use ($owner, $validated, $request) {
            $business = Business::with('currentSubscription.plan')
                ->where('id', $owner->business_id)
                ->lockForUpdate()
                ->firstOrFail();
            $limits = $business->planLimits();

            $currentUsersCount = User::where('business_id', $business->id)->count();
            if ($currentUsersCount >= $limits['max_users']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Kuota pengguna paket {$limits['plan_name']} sudah penuh ({$currentUsersCount}/{$limits['max_users']} akun). Upgrade paket untuk menambah staf lagi.",
                ], 403);
            }

            $staff = User::create([
                'business_id' => $business->id,
                'outlet_id' => $validated['outlet_id'],
                'name' => trim($validated['name']),
                'email' => $validated['email'],
                'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
                'job_title' => !empty($validated['job_title']) ? trim($validated['job_title']) : ($validated['role'] === 'admin' ? 'Admin Toko' : 'Kasir'),
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            ActivityLog::record(
                $business->id,
                $owner->id,
                'staff_created',
                'User',
                $staff->id,
                ['name' => $staff->name, 'email' => $staff->email, 'role' => $staff->role, 'outlet_id' => $staff->outlet_id],
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'message' => "Akun {$staff->role} ({$staff->name}) berhasil ditambahkan.",
                'data' => $staff->load('outlet:id,name'),
            ], 201);
        });
    }

    /**
     * Owner updates staff role, assigned outlet, active status, or password.
     */
    public function update(Request $request, $id)
    {
        $owner = $this->ensureOwner($request);
        $staff = User::where('business_id', $owner->business_id)->findOrFail($id);

        if ($staff->id === $owner->id || $staff->role === 'owner') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Pemilik (Owner) tidak dapat diubah melalui manajemen staf.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'job_title' => 'nullable|string|max:100',
            'role' => 'sometimes|required|in:admin,cashier',
            'outlet_id' => 'sometimes|required|integer|exists:outlets,id',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['outlet_id'])) {
            Outlet::where('business_id', $owner->business_id)->findOrFail($validated['outlet_id']);
        }

        $shouldRevokeTokens = (isset($validated['is_active']) && !$validated['is_active'])
            || !empty($validated['password'])
            || (isset($validated['role']) && $validated['role'] !== $staff->role);

        $updatePayload = collect($validated)->except(['password'])->toArray();
        if (!empty($validated['password'])) {
            $updatePayload['password'] = Hash::make($validated['password']);
        }

        $staff->update($updatePayload);

        if ($shouldRevokeTokens) {
            $staff->tokens()->delete();
        }

        ActivityLog::record(
            $owner->business_id,
            $owner->id,
            'staff_updated',
            'User',
            $staff->id,
            ['name' => $staff->name, 'role' => $staff->role, 'is_active' => $staff->is_active],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => "Data staf {$staff->name} berhasil diperbarui.",
            'data' => $staff->load('outlet:id,name'),
        ]);
    }

    /**
     * Owner removes a staff account.
     */
    public function destroy(Request $request, $id)
    {
        $owner = $this->ensureOwner($request);
        $staff = User::where('business_id', $owner->business_id)->findOrFail($id);

        if ($staff->id === $owner->id || $staff->role === 'owner') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Pemilik (Owner) tidak dapat dihapus.',
            ], 400);
        }

        $snapshot = ['name' => $staff->name, 'email' => $staff->email, 'role' => $staff->role];

        DB::transaction(function () use ($staff) {
            $staff->tokens()->delete();
            $staff->delete();
        });

        ActivityLog::record(
            $owner->business_id,
            $owner->id,
            'staff_deleted',
            'User',
            (int) $id,
            $snapshot,
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => "Akun staf {$snapshot['name']} telah dihapus.",
        ]);
    }

    /**
     * Owner updates business tax / footer / name / address settings.
     */
    public function updateBusinessProfile(Request $request)
    {
        $owner = $this->ensureOwner($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string|max:500',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'receipt_footer' => 'nullable|string|max:255',
        ]);

        $owner->business->update($validated);

        ActivityLog::record(
            $owner->business_id,
            $owner->id,
            'business_profile_updated',
            'Business',
            $owner->business_id,
            ['name' => $validated['name'], 'tax_percentage' => (float) $validated['tax_percentage']],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profil bisnis berhasil diperbarui.',
            'data' => $owner->business->fresh(),
        ]);
    }

    /**
     * Owner updates customizable Cashier & Admin visual/feature toggles.
     */
    public function updateFeatureSettings(Request $request)
    {
        $owner = $this->ensureOwner($request);
        $allowedKeys = array_keys(\App\Models\Business::defaultFeatureSettings());

        $rules = ['feature_settings' => 'sometimes|array'];
        foreach ($allowedKeys as $k) {
            $rules[$k] = 'sometimes|boolean';
            $rules["feature_settings.{$k}"] = 'sometimes|boolean';
        }
        $validated = $request->validate($rules);
        $incoming = array_merge(
            collect($validated)->except(['feature_settings'])->toArray(),
            is_array($validated['feature_settings'] ?? null) ? $validated['feature_settings'] : []
        );

        $current = $owner->business->resolvedFeatureSettings();
        foreach ($incoming as $k => $v) {
            if (array_key_exists($k, $current)) {
                $current[$k] = (bool) $v;
            }
        }

        $owner->business->update(['feature_settings' => $current]);

        ActivityLog::record(
            $owner->business_id,
            $owner->id,
            'business_feature_settings_updated',
            'Business',
            $owner->business_id,
            ['feature_settings' => $current],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan tombol dan akses fitur kasir/admin berhasil disimpan.',
            'data' => $owner->business->fresh()->resolvedFeatureSettings(),
        ]);
    }
}
