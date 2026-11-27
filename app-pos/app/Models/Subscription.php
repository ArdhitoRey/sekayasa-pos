<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'business_id',
        'plan_id',
        'status', // trial, active, past_due, expired, suspended, cancelled
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']) &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function daysRemaining(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function lifecycleStatus(): string
    {
        if (in_array($this->status, ['suspended', 'cancelled', 'expired'], true)) {
            return $this->status;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        $daysLeft = $this->daysRemaining();
        if ($daysLeft !== null && $daysLeft <= 7) {
            return 'expiring_soon';
        }

        return $this->status ?: 'active';
    }

    /**
     * Standardized summary for both Super Admin Monitoring and Client Self-Service views.
     */
    public function toMonitoringSummary(): array
    {
        $daysLeft = $this->daysRemaining();
        $lifecycle = $this->lifecycleStatus();
        $totalDays = ($this->starts_at && $this->expires_at)
            ? max(1, (int) $this->starts_at->copy()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay()))
            : 30;
        $elapsedDays = ($this->starts_at && $this->expires_at)
            ? max(0, min($totalDays, (int) $this->starts_at->copy()->startOfDay()->diffInDays(now()->startOfDay(), false)))
            : 0;

        $expiresLabel = $this->expires_at
            ? $this->expires_at->locale('id')->translatedFormat('d M Y')
            : 'Tanpa Batas Waktu';

        $countdownLabel = match (true) {
            $lifecycle === 'suspended' => 'Ditangguhkan Admin',
            $lifecycle === 'cancelled' => 'Dibatalkan',
            $daysLeft === null => 'Aktif Selamanya',
            $daysLeft < 0 => 'Kedaluwarsa ' . abs($daysLeft) . ' hari lalu',
            $daysLeft === 0 => 'Berakhir Hari Ini',
            $daysLeft <= 7 => "Segera Habis ({$daysLeft} hari lagi)",
            default => "{$daysLeft} hari lagi",
        };

        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan?->name ?? 'Paket Standard',
            'plan_slug' => $this->plan?->slug,
            'price_monthly' => (float) ($this->plan?->price_monthly ?? 0),
            'status' => $this->status,
            'lifecycle_status' => $lifecycle,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'starts_at_formatted' => $this->starts_at ? $this->starts_at->locale('id')->translatedFormat('d M Y') : '-',
            'expires_at_formatted' => $expiresLabel,
            'days_remaining' => $daysLeft,
            'total_days' => $totalDays,
            'progress_percent' => $this->expires_at ? (int) round(($elapsedDays / $totalDays) * 100) : 0,
            'countdown_label' => $countdownLabel,
            'is_active' => $this->isActive(),
            'is_expiring_soon' => $lifecycle === 'expiring_soon',
            'max_outlets' => (int) ($this->plan?->max_outlets ?? 1),
            'max_users' => (int) ($this->plan?->max_users ?? 2),
            'max_products' => (int) ($this->plan?->max_products ?? 150),
        ];
    }
}
