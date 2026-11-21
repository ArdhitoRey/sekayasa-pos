<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientLead;
use App\Models\Consultation;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\UatTestCase;
use App\Services\QrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientAccountController extends Controller
{
    /**
     * Client Account Overview: Profile, Available Template Plans, Active QRIS Invoices, and Custom Consultations
     */
    public function overview(Request $request)
    {
        $user = $request->user()->load('business.outlets', 'business.currentSubscription.plan', 'outlet');
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();

        $pendingPayment = null;
        $paymentHistory = [];

        if ($user->business_id) {
            $pendingPayment = SubscriptionPayment::with('plan')
                ->where('business_id', $user->business_id)
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->first();

            $paymentHistory = SubscriptionPayment::with('plan')
                ->where('business_id', $user->business_id)
                ->latest()
                ->limit(10)
                ->get();
        }

        $projects = ClientLead::where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->business_id) {
                    $q->orWhere('business_id', $user->business_id);
                }
            })
            ->with([
                'consultations',
                'discovery',
                'requirements',
                'currentProposal',
                'contracts',
                'uatCases',
                'deploymentTargets',
                'deploymentRuns',
            ])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'plans' => $plans,
                'pending_payment' => $pendingPayment,
                'payment_history' => $paymentHistory,
                'projects' => $projects,
                'qris_merchant' => [
                    'name' => QrisService::MERCHANT_NAME,
                    'nmid' => QrisService::MERCHANT_NMID,
                ],
            ],
        ]);
    }

    /**
     * Path A: Create Dynamic QRIS Payment Invoice to Rent Standard POS Template
     * Nominal = Plan Monthly Price + Unique 3-Digit Code so incoming funds can be auto-detected.
     */
    public function rentTemplate(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        if (!$user->business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak terhubung dengan profil bisnis.',
            ], 400);
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $business = $user->business;

        return DB::transaction(function () use ($business, $plan) {
            // Cancel older pending invoices for this business to avoid stale duplicates
            SubscriptionPayment::where('business_id', $business->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'notes' => 'Digantikan tagihan QRIS baru']);

            $baseAmount = (int) round((float) $plan->price_monthly);

            // Pick a unique 3-digit code not currently in use by any active pending QRIS payment
            $activeAmounts = SubscriptionPayment::where('status', 'pending')
                ->where('expires_at', '>', now())
                ->pluck('amount')
                ->map(fn ($a) => (int) round((float) $a))
                ->toArray();

            $uniqueCode = random_int(101, 999);
            $attempts = 0;
            while (in_array($baseAmount + $uniqueCode, $activeAmounts, true) && $attempts < 50) {
                $uniqueCode = random_int(101, 999);
                $attempts++;
            }

            $totalAmount = $baseAmount + $uniqueCode;
            $qrisPayload = QrisService::generateDynamic($totalAmount);
            $invoiceNumber = 'SUB-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $payment = SubscriptionPayment::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'invoice_number' => $invoiceNumber,
                'base_amount' => $baseAmount,
                'unique_code' => $uniqueCode,
                'amount' => $totalAmount,
                'payment_method' => 'qris_dynamic',
                'qris_payload' => $qrisPayload,
                'status' => 'pending',
                'notes' => "Tagihan sewa Paket {$plan->name} (Kode unik {$uniqueCode})",
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Tagihan QRIS Dinamis dibuat sebesar Rp " . number_format($totalAmount, 0, ',', '.') . ". Silakan scan untuk aktivasi otomatis.",
                'data' => [
                    'payment' => $payment->load('plan'),
                    'merchant' => [
                        'name' => QrisService::MERCHANT_NAME,
                        'nmid' => QrisService::MERCHANT_NMID,
                    ],
                ],
            ], 201);
        });
    }

    /**
     * Poll QRIS Payment Status for Auto-Detection UI on /client
     */
    public function checkPaymentStatus(Request $request, $paymentId)
    {
        $user = $request->user();
        $payment = SubscriptionPayment::with('plan', 'business.currentSubscription.plan')
            ->where('business_id', $user->business_id)
            ->findOrFail($paymentId);

        $isExpired = $payment->status === 'pending' && $payment->expires_at && $payment->expires_at->isPast();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'payment_status' => $isExpired ? 'expired' : $payment->status,
                'amount' => (float) $payment->amount,
                'verified_at' => $payment->verified_at,
                'service_type' => $payment->business->service_type,
            ],
        ]);
    }

    /**
     * Path B: Request Custom Consultation First (Business Process Does Not Fit Standard Template)
     */
    public function requestConsultation(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'business_type' => 'required|string|max:100',
            'scale' => 'required|string|max:100',
            'template_gap_notes' => 'required|string|min:10',
            'preferred_consultation_at' => 'required|date',
            'phone' => 'nullable|string|max:25',
        ]);

        $business = $user->business;

        return DB::transaction(function () use ($user, $business, $validated) {
            if (!empty($validated['phone'])) {
                $user->update(['phone' => $validated['phone']]);
                if ($business) {
                    $business->update(['phone' => $validated['phone']]);
                }
            }

            $lead = ClientLead::create([
                'user_id' => $user->id,
                'business_id' => $business ? $business->id : null,
                'client_name' => $user->name,
                'business_name' => $business ? $business->name : $user->name,
                'email' => $user->email,
                'phone' => $validated['phone'] ?? $user->phone,
                'business_type' => "{$validated['business_type']} ({$validated['scale']})",
                'source' => 'Portal Akun Klien (Custom Konsultasi)',
                'status' => 'consultation_scheduled',
                'notes' => 'Ketidaksesuaian Template: ' . $validated['template_gap_notes'],
                'template_gap_notes' => $validated['template_gap_notes'],
                'preferred_consultation_at' => $validated['preferred_consultation_at'],
            ]);

            Consultation::create([
                'lead_id' => $lead->id,
                'scheduled_at' => $validated['preferred_consultation_at'],
                'pic_name' => 'Business Analyst Seka Yasa',
                'consultation_notes' => $validated['template_gap_notes'],
                'status' => 'scheduled',
            ]);

            if ($business && $business->service_type === 'none') {
                $business->update(['service_type' => 'custom_pos']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan konsultasi custom berhasil dijadwalkan dan diteruskan ke tim analis kami.',
                'data' => $lead->load('consultations'),
            ], 201);
        });
    }

    /**
     * Client submits UAT result strictly on their own custom project (OPS-026 / OPS-028)
     */
    public function submitOwnUat(Request $request, $testCaseId)
    {
        $user = $request->user();
        $validated = $request->validate([
            'status' => 'required|in:pass,fail,blocked',
            'client_feedback' => 'nullable|string',
        ]);

        $testCase = UatTestCase::with('lead')->findOrFail($testCaseId);

        $isOwner = $testCase->lead && (
            $testCase->lead->user_id === $user->id ||
            ($user->business_id && $testCase->lead->business_id === $user->business_id)
        );

        if (!$isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki otorisasi atas proyek ini.',
            ], 403);
        }

        $testCase->update([
            'status' => $validated['status'],
            'client_feedback' => $validated['client_feedback'] ?? null,
            'tested_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Hasil pengujian UAT Anda telah disimpan.',
            'data' => $testCase,
        ]);
    }
}
