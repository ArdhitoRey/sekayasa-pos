<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\QrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrisWebhookController extends Controller
{
    /**
     * Receive incoming QRIS payment notification / bank mutation webhook.
     * Compatible with Android Notification Forwarders (MacroDroid, Tasker, etc.)
     * listening to GoPay Merchant / QRIS push notifications.
     */
    public function handleMutation(Request $request)
    {
        $expectedSecret = config('services.qris.webhook_secret', env('QRIS_WEBHOOK_SECRET', 'seka-yasa-qris-2026'));
        $providedSecret = $request->header('X-QRIS-WEBHOOK-SECRET') ?? $request->input('secret');

        if ($providedSecret !== $expectedSecret) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized webhook secret.',
            ], 401);
        }

        // Parse exact amount from numeric 'amount' or notification text 'message'
        $amount = null;
        if ($request->filled('amount') && is_numeric($request->input('amount'))) {
            $amount = (int) round((float) $request->input('amount'));
        } elseif ($request->filled('message') || $request->filled('notification_text')) {
            $text = $request->input('message') ?? $request->input('notification_text');
            $amount = QrisService::extractAmountFromNotification((string) $text);
        }

        if (!$amount || $amount <= 0) {
            return response()->json([
                'status' => 'error',
                'matched' => false,
                'message' => 'Nominal pembayaran tidak ditemukan pada payload notifikasi.',
            ], 422);
        }

        return DB::transaction(function () use ($amount, $request) {
            $payment = SubscriptionPayment::where('status', 'pending')
                ->where('amount', $amount)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'ignored',
                    'matched' => false,
                    'parsed_amount' => $amount,
                    'message' => "Tidak ada tagihan QRIS pending yang cocok dengan nominal Rp " . number_format($amount, 0, ',', '.'),
                ], 404);
            }

            // 1. Mark payment verified
            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
                'webhook_payload' => $request->all(),
                'notes' => ($payment->notes ? $payment->notes . ' | ' : '') . 'Terdeteksi otomatis via QRIS Notification Webhook',
            ]);

            // 2. Activate subscription for the business
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

            // 3. Unlock POS SaaS service on the business
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
                'matched' => true,
                'message' => "Pembayaran {$payment->invoice_number} (Rp " . number_format($amount, 0, ',', '.') . ") terverifikasi otomatis. Layanan POS telah diaktifkan!",
                'data' => [
                    'payment' => $payment->fresh('plan', 'business'),
                    'subscription' => $subscription,
                ],
            ]);
        });
    }

    /**
     * List subscription QRIS payments for internal portal monitoring (/portal)
     */
    public function listPayments()
    {
        $payments = SubscriptionPayment::with(['business', 'plan'])
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'payments' => $payments,
                'webhook_url' => url('/api/webhooks/qris-mutation'),
                'webhook_secret' => env('QRIS_WEBHOOK_SECRET', 'seka-yasa-qris-2026'),
                'merchant_name' => QrisService::MERCHANT_NAME,
                'merchant_nmid' => QrisService::MERCHANT_NMID,
            ],
        ]);
    }
}
