<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
{
    /**
     * List Outlets (FR-007)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses daftar outlet hanya tersedia untuk akun bisnis.',
            ], 403);
        }

        $query = Outlet::where('business_id', $user->business_id)->orderBy('id');
        if ($user->role === 'cashier') {
            $query->where('id', $user->outlet_id ?: 0)->where('is_active', true);
        } elseif ($user->role !== 'owner' || $request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $outlets = $query->get();
        if ($user->role === 'cashier') {
            $outlets->each->makeHidden('feature_overrides');
        }

        return response()->json([
            'status' => 'success',
            'data' => $outlets,
        ]);
    }

    /**
     * Create Outlet with Plan Limitation Check (FR-007, FR-060)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat menambah outlet cabang.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'outlet_code' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'feature_overrides' => 'nullable|array',
        ]);

        // Enforce subscription plan limit atomically under business row lock (FR-060)
        return DB::transaction(function () use ($user, $validated) {
            $business = Business::where('id', $user->business_id)->lockForUpdate()->first();
            $limits = $business ? $business->planLimits() : ['max_outlets' => 1];
            $maxOutlets = $limits['max_outlets'];

            $currentCount = Outlet::where('business_id', $user->business_id)->where('is_active', true)->count();
            if ($currentCount >= $maxOutlets) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Batas maksimal outlet untuk paket langganan Anda ({$maxOutlets} outlet) telah tercapai. Silakan upgrade paket langganan Anda.",
                ], 403);
            }

            $outlet = Outlet::create([
                'business_id' => $user->business_id,
                'name' => $validated['name'],
                'outlet_code' => !empty($validated['outlet_code']) ? strtoupper(trim($validated['outlet_code'])) : null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'receipt_header' => $validated['receipt_header'] ?? null,
                'receipt_footer' => $validated['receipt_footer'] ?? null,
                'tax_percentage' => $validated['tax_percentage'] ?? null,
                'feature_overrides' => $validated['feature_overrides'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Outlet baru berhasil ditambahkan',
                'data' => $outlet,
            ], 201);
        });
    }

    /**
     * Update Outlet & Branch Overrides (FR-007)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengubah data outlet.',
            ], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'outlet_code' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'feature_overrides' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if (array_key_exists('outlet_code', $validated) && !empty($validated['outlet_code'])) {
            $validated['outlet_code'] = strtoupper(trim($validated['outlet_code']));
        }

        return DB::transaction(function () use ($user, $id, $validated) {
            $business = Business::where('id', $user->business_id)->lockForUpdate()->first();
            $outlet = Outlet::where('business_id', $user->business_id)->lockForUpdate()->findOrFail($id);

            if (!empty($validated['is_active']) && !$outlet->is_active) {
                $limits = $business ? $business->planLimits() : ['max_outlets' => 1];
                $maxOutlets = $limits['max_outlets'];
                $activeOthers = Outlet::where('business_id', $user->business_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $outlet->id)
                    ->count();
                if ($activeOthers >= $maxOutlets) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Batas maksimal outlet untuk paket langganan Anda ({$maxOutlets} outlet) telah tercapai. Silakan upgrade paket langganan Anda.",
                    ], 403);
                }
            }

            $outlet->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan outlet berhasil diperbarui',
                'data' => $outlet->fresh(),
            ]);
        });
    }
}
