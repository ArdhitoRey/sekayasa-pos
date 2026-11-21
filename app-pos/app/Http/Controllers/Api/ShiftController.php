<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Check Current Shift (FR-035)
     */
    public function current(Request $request)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $shift = CashierShift::where('outlet_id', $outletId)
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
        $user = $request->user();
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'initial_cash' => 'required|numeric|min:0', // Modal awal
            'notes' => 'nullable|string',
        ]);

        // Check if there is already an open shift
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

        return response()->json([
            'status' => 'success',
            'message' => 'Shift kasir berhasil dibuka',
            'data' => $shift,
        ], 201);
    }

    /**
     * Close Cashier Shift & Cash Reconciliation (FR-036)
     */
    public function close(Request $request, $id)
    {
        $user = $request->user();
        $shift = CashierShift::where('business_id', $user->business_id)
            ->where('status', 'open')
            ->findOrFail($id);

        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0', // Uang fisik di laci
            'notes' => 'nullable|string',
        ]);

        $initialCash = (float) $shift->initial_cash;
        $cashSales = (float) $shift->cash_sales_total;
        $expectedCash = $initialCash + $cashSales;
        $actualCash = (float) $validated['actual_cash'];
        $cashVariance = $actualCash - $expectedCash; // Selisih (+ lebih, - kurang)

        $shift->update([
            'closed_at' => now(),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_variance' => $cashVariance,
            'status' => 'closed',
            'notes' => $validated['notes'] ?? $shift->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shift kasir berhasil ditutup',
            'data' => [
                'shift' => $shift,
                'summary' => [
                    'opened_at' => $shift->opened_at->format('d/m/Y H:i'),
                    'closed_at' => $shift->closed_at->format('d/m/Y H:i'),
                    'initial_cash' => $initialCash,
                    'cash_sales' => $cashSales,
                    'non_cash_sales' => (float) $shift->non_cash_sales_total,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'cash_variance' => $cashVariance,
                    'variance_status' => $cashVariance == 0 ? 'MATCH' : ($cashVariance > 0 ? 'SURPLUS' : 'DEFICIT'),
                ],
            ],
        ]);
    }

    /**
     * Shift History (FR-037)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $shifts = CashierShift::where('business_id', $user->business_id)
            ->where('outlet_id', $outletId)
            ->with('cashier')
            ->latest('opened_at')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $shifts,
        ]);
    }
}
