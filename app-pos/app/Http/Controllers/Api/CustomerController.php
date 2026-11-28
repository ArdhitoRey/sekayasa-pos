<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Outlet;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function checkCashierCustomerFeature($user): ?\Illuminate\Http\JsonResponse
    {
        if ($user->role === 'cashier' && $user->business) {
            $outlet = $user->outlet_id ? Outlet::where('business_id', $user->business_id)->find($user->outlet_id) : null;
            $settings = $user->business->resolvedFeatureSettings($outlet);
            if (empty($settings['pos_allow_customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemilik bisnis menonaktifkan fitur data pelanggan untuk kasir pada outlet ini.',
                ], 403);
            }
        }
        return null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
            return response()->json(['status' => 'error', 'message' => 'Data pelanggan hanya tersedia untuk akun bisnis.'], 403);
        }
        if ($resp = $this->checkCashierCustomerFeature($user)) {
            return $resp;
        }
        $customers = Customer::where('business_id', $user->business_id)
            ->withCount('transactions')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
            return response()->json(['status' => 'error', 'message' => 'Hanya akun bisnis yang dapat menambah pelanggan.'], 403);
        }
        if ($resp = $this->checkCashierCustomerFeature($user)) {
            return $resp;
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'business_id' => $user->business_id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pelanggan berhasil ditambahkan',
            'data' => $customer,
        ], 201);
    }
}
