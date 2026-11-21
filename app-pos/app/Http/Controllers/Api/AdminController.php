<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientLead;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function ensureSuperAdmin(Request $request)
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Akses khusus Super Admin.');
        }
    }

    /**
     * List Subscription Payments & Custom Consultations for Super Admin in /dashboard
     */
    public function overview(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $payments = SubscriptionPayment::with(['business', 'plan'])
            ->latest()
            ->limit(30)
            ->get();

        $consultations = ClientLead::with(['consultations', 'user'])
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscription_payments' => $payments,
                'consultations' => $consultations,
            ],
        ]);
    }

    /**
     * Verify Pending Subscription Payment (while Midtrans integration is pending)
     */
    public function verifySubscriptionPayment(Request $request, $paymentId)
    {
        $this->ensureSuperAdmin($request);

        $payment = SubscriptionPayment::with('business', 'plan')->findOrFail($paymentId);

        if ($payment->status === 'verified') {
            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran ini sudah diverifikasi sebelumnya.',
                'data' => $payment,
            ]);
        }

        return DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'notes' => ($payment->notes ? $payment->notes . ' | ' : '') . 'Diverifikasi Super Admin (Midtrans dalam pengajuan)',
            ]);

            $business = $payment->business;
            $subscription = Subscription::updateOrCreate(
                ['business_id' => $business->id],
                [
                    'plan_id' => $payment->plan_id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addMonth(),
                ]
            );

            $payment->update(['subscription_id' => $subscription->id]);
            $business->update(['service_type' => 'template_saas']);

            if ($business->outlets()->count() === 0) {
                Outlet::create([
                    'business_id' => $business->id,
                    'name' => 'Outlet Utama',
                    'address' => $business->address ?? 'Pusat',
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => "Pembayaran {$payment->invoice_number} berhasil diverifikasi dan layanan POS klien telah diaktifkan.",
                'data' => [
                    'payment' => $payment->fresh('business', 'plan'),
                    'subscription' => $subscription,
                ],
            ]);
        });
    }
}
