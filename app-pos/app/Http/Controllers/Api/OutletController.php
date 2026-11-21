<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    /**
     * List Outlets (FR-007)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $outlets = Outlet::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->get();

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

        // Enforce subscription plan limit (FR-060)
        $business = $user->business;
        $sub = $business ? $business->currentSubscription : null;
        $maxOutlets = ($sub && $sub->plan) ? $sub->plan->max_outlets : 1;

        $currentCount = Outlet::where('business_id', $user->business_id)->where('is_active', true)->count();
        if ($currentCount >= $maxOutlets) {
            return response()->json([
                'status' => 'error',
                'message' => "Batas maksimal outlet untuk paket langganan Anda ({$maxOutlets} outlet) telah tercapai. Silakan upgrade paket langganan Anda.",
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Outlet baru berhasil ditambahkan',
            'data' => $outlet,
        ], 201);
    }

    /**
     * Update Outlet (FR-007)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $outlet = Outlet::where('business_id', $user->business_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $outlet->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Outlet berhasil diperbarui',
            'data' => $outlet,
        ]);
    }
}
