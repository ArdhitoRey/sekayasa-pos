<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'business_id',
        'subscription_id',
        'plan_id',
        'plan_name_snapshot',
        'duration_months',
        'duration_days',
        'invoice_number',
        'receipt_number',
        'base_amount',
        'unique_code',
        'amount',
        'payment_method',
        'qris_payload',
        'proof_file',
        'status', // pending, verified, rejected
        'notes',
        'webhook_payload',
        'verified_by',
        'verified_at',
        'billing_starts_at',
        'billing_expires_at',
        'expires_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'duration_months' => 'integer',
        'duration_days' => 'integer',
        'unique_code' => 'integer',
        'webhook_payload' => 'array',
        'verified_at' => 'datetime',
        'billing_starts_at' => 'datetime',
        'billing_expires_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function billingPeriods(): array
    {
        return [
            1 => ['months' => 1, 'days' => 30, 'label' => '1 Bulan'],
            3 => ['months' => 3, 'days' => 90, 'label' => '3 Bulan'],
            6 => ['months' => 6, 'days' => 180, 'label' => '6 Bulan'],
            12 => ['months' => 12, 'days' => 365, 'label' => '1 Tahun'],
            24 => ['months' => 24, 'days' => 730, 'label' => '2 Tahun'],
            36 => ['months' => 36, 'days' => 1095, 'label' => '3 Tahun'],
        ];
    }

    public function resolvedDurationLabel(): string
    {
        $months = (int) ($this->duration_months ?: 1);
        $periods = self::billingPeriods();
        if (isset($periods[$months])) {
            return $periods[$months]['label'];
        }
        return $months >= 12 && $months % 12 === 0
            ? ($months / 12) . ' Tahun'
            : $months . ' Bulan';
    }

    public static function generateReceiptNumber(): string
    {
        return 'KW-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    public function resolvedReceiptNumber(): ?string
    {
        if ($this->status !== 'verified') {
            return null;
        }
        if ($this->receipt_number) {
            return $this->receipt_number;
        }
        $datePart = ($this->verified_at ?? $this->created_at ?? now())->format('Ymd');
        return 'KW-' . $datePart . '-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Format clean official subscription payment receipt payload for printing/viewing.
     */
    public function toReceiptPayload(): array
    {
        $this->loadMissing(['business', 'plan', 'subscription']);

        $paidAt = $this->verified_at ?? $this->updated_at ?? now();
        $periodStart = $this->billing_starts_at ?? $this->subscription?->starts_at ?? $paidAt;
        $periodEnd = $this->billing_expires_at ?? $this->subscription?->expires_at;

        return [
            'payment_id' => $this->id,
            'receipt_number' => $this->resolvedReceiptNumber(),
            'invoice_number' => $this->invoice_number,
            'status' => 'LUNAS / TERVERIFIKASI',
            'paid_at' => $paidAt->toIso8601String(),
            'paid_at_formatted' => $paidAt->locale('id')->translatedFormat('d M Y, H:i') . ' WIB',
            'issuer_name' => 'Tandha by SEKA GROUP',
            'issuer_tagline' => 'Sistem Kasir & Manajemen Cabang',
            'business_name' => trim((string) (($this->business?->legal_type ? $this->business->legal_type . ' ' : '') . ($this->business?->name ?? '-'))),
            'owner_name' => $this->business?->owner_name ?? '-',
            'owner_email' => $this->business?->email ?? '-',
            'owner_phone' => $this->business?->phone ?? '-',
            'npwp' => $this->business?->npwp ?: '-',
            'plan_name' => $this->plan_name_snapshot ?? $this->plan?->name ?? '-',
            'duration_months' => (int) ($this->duration_months ?: 1),
            'duration_days' => (int) ($this->duration_days ?: 30),
            'duration_label' => $this->resolvedDurationLabel(),
            'plan_limits' => $this->plan ? [
                'max_outlets' => (int) $this->plan->max_outlets,
                'max_users' => (int) $this->plan->max_users,
                'max_products' => (int) $this->plan->max_products,
            ] : null,
            'billing_starts_at_formatted' => $periodStart ? $periodStart->locale('id')->translatedFormat('d M Y') : '-',
            'billing_expires_at_formatted' => $periodEnd ? $periodEnd->locale('id')->translatedFormat('d M Y') : '-',
            'payment_method' => strtoupper(str_replace('_', ' ', (string) $this->payment_method)),
            'base_amount' => (float) $this->base_amount,
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Atomically verify a pending payment, issue a unique receipt number, and activate/extend the business subscription.
     */
    public function approveAndActivate(?int $verifiedByUserId, string $paymentMethodLabel, string $approvalNote, array $webhookPayload = []): array
    {
        return DB::transaction(function () use ($verifiedByUserId, $paymentMethodLabel, $approvalNote, $webhookPayload) {
            $locked = self::whereKey($this->id)->with(['business', 'plan', 'subscription'])->lockForUpdate()->firstOrFail();

            if ($locked->status === 'verified') {
                return [
                    'already_verified' => true,
                    'payment' => $locked,
                    'subscription' => $locked->subscription,
                ];
            }

            if ($locked->status !== 'pending') {
                throw new \InvalidArgumentException('Tagihan ini sudah dibatalkan/ditolak dan tidak dapat diaktifkan.');
            }

            $days = max(1, (int) ($locked->duration_days ?: 30));
            $subscription = $locked->business->applySubscriptionPeriod($locked->plan, $days, 'active');

            $locked->update([
                'status' => 'verified',
                'subscription_id' => $subscription->id,
                'plan_name_snapshot' => $locked->plan?->name,
                'receipt_number' => $locked->receipt_number ?: self::generateReceiptNumber(),
                'payment_method' => $paymentMethodLabel ?: $locked->payment_method,
                'verified_by' => $verifiedByUserId,
                'verified_at' => now(),
                'billing_starts_at' => $subscription->starts_at,
                'billing_expires_at' => $subscription->expires_at,
                'webhook_payload' => !empty($webhookPayload) ? $webhookPayload : $locked->webhook_payload,
                'notes' => trim(($locked->notes ? $locked->notes . ' | ' : '') . $approvalNote),
            ]);

            return [
                'already_verified' => false,
                'payment' => $locked->fresh(['business', 'plan', 'subscription']),
                'subscription' => $subscription,
            ];
        });
    }
}
