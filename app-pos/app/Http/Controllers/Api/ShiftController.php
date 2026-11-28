<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    private function ensureCashier(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'cashier') {
            return response()->json([
                'status' => 'error',
                'code' => 'CASHIER_ROLE_REQUIRED',
                'message' => 'Operasional shift & kasir hanya dapat dilakukan oleh akun Kasir (Cashier). Pemilik bisnis hanya dapat mengakses Mode Preview (Read-Only).',
            ], 403);
        }

        return null;
    }

    /**
     * Check Current Shift (FR-035)
     */
    public function current(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'cashier') {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        $requestedOutletId = $request->query('outlet_id');
        if ($requestedOutletId !== null && $requestedOutletId !== '') {
            Outlet::where('business_id', $user->business_id)->findOrFail($requestedOutletId);
            if (!$user->outlet_id || (int) $requestedOutletId !== (int) $user->outlet_id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'OUTLET_ACCESS_DENIED',
                    'message' => 'Anda hanya dapat melihat shift pada outlet tempat Anda ditugaskan.',
                ], 403);
            }
        }

        if (!$user->outlet_id) {
            return response()->json([
                'status' => 'error',
                'code' => 'OUTLET_ACCESS_DENIED',
                'message' => 'Akun kasir belum ditetapkan ke outlet mana pun.',
            ], 403);
        }

        $shift = CashierShift::where('outlet_id', $user->outlet_id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $shift,
        ]);
    }

    /**
     * Open Cashier Shift (FR-035)
     */
    public function open(Request $request)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'initial_cash' => 'required|numeric|min:0', // Modal awal
            'notes' => 'nullable|string',
        ]);

        $outlet = Outlet::where('business_id', $user->business_id)->findOrFail($validated['outlet_id']);

        if (!$user->outlet_id || (int) $validated['outlet_id'] !== (int) $user->outlet_id) {
            return response()->json([
                'status' => 'error',
                'code' => 'OUTLET_ACCESS_DENIED',
                'message' => 'Anda hanya dapat membuka shift pada outlet tempat Anda ditugaskan.',
            ], 403);
        }

        if (!$outlet->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet ini sedang dinonaktifkan oleh pemilik bisnis.',
            ], 422);
        }

        return DB::transaction(function () use ($user, $validated, $request) {
            // Serialize concurrent shift opens for this cashier
            User::whereKey($user->id)->lockForUpdate()->first();

            $existing = CashierShift::where('outlet_id', $validated['outlet_id'])
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda masih memiliki shift aktif yang belum ditutup.',
                    'data' => $existing,
                ], 400);
            }

            $shift = CashierShift::create([
                'business_id' => $user->business_id,
                'outlet_id' => $validated['outlet_id'],
                'user_id' => $user->id,
                'opened_at' => now(),
                'initial_cash' => $validated['initial_cash'],
                'cash_sales_total' => 0,
                'non_cash_sales_total' => 0,
                'expected_cash' => $validated['initial_cash'],
                'status' => 'open',
                'notes' => $validated['notes'] ?? null,
            ]);

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'shift_opened',
                'CashierShift',
                $shift->id,
                ['outlet_id' => $validated['outlet_id'], 'initial_cash' => (float) $validated['initial_cash']],
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Shift kasir berhasil dibuka',
                'data' => $shift,
            ], 201);
        });
    }

    /**
     * Close Cashier Shift & Cash Reconciliation (FR-036)
     */
    public function close(Request $request, $id)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();
        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0', // Uang fisik di laci
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($user, $id, $validated, $request) {
            $shift = CashierShift::where('business_id', $user->business_id)
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$user->outlet_id || (int) $shift->outlet_id !== (int) $user->outlet_id || (int) $shift->user_id !== (int) $user->id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'SHIFT_ACCESS_DENIED',
                    'message' => 'Anda tidak berhak menutup shift milik kasir atau outlet lain.',
                ], 403);
            }

            if ($shift->status !== 'open') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shift kasir ini sudah ditutup sebelumnya.',
                ], 400);
            }

            $initialCash = (float) $shift->initial_cash;
            $cashSales = (float) $shift->cash_sales_total;
            $expectedCash = $initialCash + $cashSales;
            $actualCash = (float) $validated['actual_cash'];
            $cashVariance = $actualCash - $expectedCash; // Selisih (+ lebih, - kurang)
            $varianceStatus = $cashVariance == 0 ? 'MATCH' : ($cashVariance > 0 ? 'SURPLUS' : 'DEFICIT');

            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_variance' => $cashVariance,
                'status' => 'closed',
                'notes' => $validated['notes'] ?? $shift->notes,
            ]);

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'shift_closed',
                'CashierShift',
                $shift->id,
                [
                    'outlet_id' => $shift->outlet_id,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'cash_variance' => $cashVariance,
                    'variance_status' => $varianceStatus,
                ],
                $request->ip()
            );

            $txCount = \App\Models\Transaction::where('shift_id', $shift->id)
                ->where('payment_status', 'paid')
                ->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Shift kasir berhasil ditutup',
                'data' => [
                    'shift' => $shift,
                    'summary' => [
                        'opened_at' => $shift->opened_at->format('d/m/Y H:i'),
                        'closed_at' => $shift->closed_at->format('d/m/Y H:i'),
                        'transactions_count' => $txCount,
                        'initial_cash' => $initialCash,
                        'cash_sales' => $cashSales,
                        'non_cash_sales' => (float) $shift->non_cash_sales_total,
                        'expected_cash' => $expectedCash,
                        'actual_cash' => $actualCash,
                        'cash_variance' => $cashVariance,
                        'variance_status' => $varianceStatus,
                    ],
                ],
            ]);
        });
    }

    /**
     * Shift History (FR-037)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Riwayat shift kasir hanya dapat dilihat oleh Owner atau Admin.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_view_shift_history'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses riwayat shift untuk Admin.'], 403);
            }
        }
        $query = CashierShift::where('business_id', $user->business_id)
            ->with(['cashier', 'outlet'])
            ->latest('opened_at');

        if ($outletId = $request->query('outlet_id')) {
            Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
            $query->where('outlet_id', $outletId);
        }

        $shifts = $query->paginate(100);

        return response()->json([
            'status' => 'success',
            'data' => $shifts,
        ]);
    }
}
