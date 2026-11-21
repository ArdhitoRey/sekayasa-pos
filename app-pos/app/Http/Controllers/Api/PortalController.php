<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientLead;
use App\Models\Consultation;
use App\Models\DiscoverySession;
use App\Models\ProjectContract;
use App\Models\ProjectRequirement;
use App\Models\Proposal;
use App\Models\UatTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    /**
     * List all projects / client pipeline (PRD v2 Bab 5.1)
     */
    public function index(Request $request)
    {
        $query = ClientLead::with(['consultations', 'discovery', 'currentProposal', 'contracts', 'deploymentTargets', 'deploymentRuns'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->get()->map(function ($lead) {
            $devContract = $lead->contracts->where('type', 'development')->first();
            $lead->payment_verified = $devContract && $devContract->payment_status === 'verified_100_percent';
            $lead->total_cost = $devContract ? (float)$devContract->total_amount : ($lead->currentProposal ? (float)$lead->currentProposal->development_cost : 0);
            $lead->monthly_cost = $lead->currentProposal ? (float)$lead->currentProposal->monthly_maintenance_cost : 0;
            return $lead;
        });

        return response()->json([
            'status' => 'success',
            'data' => $leads,
        ]);
    }

    /**
     * Get Client Project Full Detail
     */
    public function show($id)
    {
        $lead = ClientLead::with([
            'consultations',
            'discovery',
            'requirements',
            'proposals',
            'contracts',
            'uatCases.requirement',
            'deploymentTargets',
            'deploymentRuns.target',
            'subscription',
            'tickets'
        ])->findOrFail($id);

        $devContract = $lead->contracts->where('type', 'development')->first();
        $monthlyContract = $lead->contracts->where('type', 'monthly_service')->first();

        // Calculate UAT Progress
        $totalUat = $lead->uatCases->count();
        $passedUat = $lead->uatCases->where('status', 'pass')->count();
        $failedUat = $lead->uatCases->where('status', 'fail')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'lead' => $lead,
                'dev_contract' => $devContract,
                'monthly_contract' => $monthlyContract,
                'is_payment_100_verified' => $devContract && $devContract->payment_status === 'verified_100_percent',
                'uat_summary' => [
                    'total' => $totalUat,
                    'passed' => $passedUat,
                    'failed' => $failedUat,
                    'all_passed' => $totalUat > 0 && $passedUat === $totalUat,
                ],
            ],
        ]);
    }

    /**
     * Create New Lead Inquiry (OPS-001)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'business_type' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $lead = ClientLead::create([
            ...$validated,
            'source' => 'Portal Internal Inquiry',
            'status' => 'new_lead',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead klien baru berhasil ditambahkan',
            'data' => $lead,
        ], 201);
    }

    /**
     * Advance Pipeline Status (PRD v2 Bab 5.1)
     */
    public function updateStatus(Request $request, $id)
    {
        $lead = ClientLead::with('contracts', 'uatCases')->findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $newStatus = $validated['status'];

        // Enforce Payment Gate (Bab 1.1 point 3, OPS-016)
        if ($newStatus === 'ready_for_dev') {
            $devContract = $lead->contracts->where('type', 'development')->first();
            if (!$devContract || $devContract->payment_status !== 'verified_100_percent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment Gate: Development tidak dapat dimulai sebelum pembayaran 100% diverifikasi lunas.',
                ], 422);
            }
        }

        // Enforce UAT Gate (Bab 1.1 point 5, OPS-029)
        if (in_array($newStatus, ['ready_to_deploy', 'live'])) {
            $totalUat = $lead->uatCases->count();
            $passedUat = $lead->uatCases->where('status', 'pass')->count();
            if ($totalUat === 0 || $passedUat < $totalUat) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'UAT Gate: Seluruh test case UAT wajib disetujui klien (Pass) sebelum siap deploy ke produksi.',
                ], 422);
            }
        }

        $lead->update(['status' => $newStatus]);

        return response()->json([
            'status' => 'success',
            'message' => "Status proyek berhasil diperbarui ke '{$newStatus}'",
            'data' => $lead,
        ]);
    }

    /**
     * Verify 100% Development Payment (Payment Gate OPS-016)
     */
    public function verifyPayment(Request $request, $contractId)
    {
        $contract = ProjectContract::findOrFail($contractId);
        $validated = $request->validate([
            'payment_reference' => 'required|string',
        ]);

        DB::transaction(function () use ($contract, $validated) {
            $contract->update([
                'payment_status' => 'verified_100_percent',
                'payment_verified_at' => now(),
                'payment_reference' => $validated['payment_reference'],
            ]);

            // Auto-advance lead to ready_for_dev if contract is signed
            if ($contract->type === 'development' && $contract->is_signed) {
                $contract->lead->update(['status' => 'ready_for_dev']);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran 100% berhasil diverifikasi. Proyek telah dibuka untuk development!',
            'data' => $contract->fresh(),
        ]);
    }

    /**
     * Submit UAT Test Result (OPS-028)
     */
    public function submitUat(Request $request, $testCaseId)
    {
        $testCase = UatTestCase::findOrFail($testCaseId);
        $validated = $request->validate([
            'status' => 'required|in:pass,fail,blocked',
            'client_feedback' => 'nullable|string',
        ]);

        $testCase->update([
            'status' => $validated['status'],
            'client_feedback' => $validated['client_feedback'] ?? null,
            'tested_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Hasil UAT berhasil dicatat',
            'data' => $testCase,
        ]);
    }
}
