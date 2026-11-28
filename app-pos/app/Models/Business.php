<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Business extends Model
{
    protected $fillable = [
        'name',
        'legal_type',
        'business_category',
        'owner_name',
        'email',
        'phone',
        'npwp',
        'nib',
        'address',
        'city',
        'province',
        'postal_code',
        'outlet_count_estimate',
        'logo',
        'currency',
        'timezone',
        'tax_percentage',
        'receipt_footer',
        'is_active',
        'service_type',
        'feature_settings',
    ];

    protected $casts = [
        'tax_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'feature_settings' => 'array',
    ];

    /**
     * Default customizable feature switches for Cashier POS & Admin Toko.
     */
    public static function defaultFeatureSettings(): array
    {
        return [
            'pos_allow_discount' => true,
            'pos_allow_customer' => true,
            'pos_allow_notes' => true,
            'pos_allow_barcode' => true,
            'pos_allow_history' => true,
            'pos_allow_void' => true,
            'pos_require_shift' => true,
            'admin_view_dashboard' => true,
            'admin_manage_products' => true,
            'admin_manage_categories' => true,
            'admin_manage_inventory' => true,
            'admin_view_sales_report' => true,
            'admin_view_shift_history' => true,
        ];
    }

    /**
     * Effective feature settings merged from defaults, business settings, and optional branch override.
     */
    public function resolvedFeatureSettings(?Outlet $outlet = null): array
    {
        $resolved = self::defaultFeatureSettings();
        $bizSettings = is_array($this->feature_settings) ? $this->feature_settings : [];
        foreach ($resolved as $key => $defaultVal) {
            if (array_key_exists($key, $bizSettings) && $bizSettings[$key] !== null) {
                $resolved[$key] = (bool) $bizSettings[$key];
            }
        }

        if ($outlet && is_array($outlet->feature_overrides)) {
            foreach ($outlet->feature_overrides as $key => $val) {
                if (array_key_exists($key, $resolved) && $val !== null) {
                    $resolved[$key] = (bool) $val;
                }
            }
        }

        return $resolved;
    }

    public function outlets()
    {
        return $this->hasMany(Outlet::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latest('id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function clientLeads()
    {
        return $this->hasMany(ClientLead::class);
    }

    /**
     * Effective plan limits (outlets, total staff+owner users, active products).
     */
    public function planLimits(): array
    {
        if (!$this->is_active) {
            return [
                'plan_name' => 'Akun Dibekukan',
                'max_outlets' => 0,
                'max_users' => 0,
                'max_products' => 0,
            ];
        }

        $sub = $this->relationLoaded('currentSubscription')
            ? $this->currentSubscription
            : $this->currentSubscription()->with('plan')->first();

        if ($sub && $sub->plan && $sub->isActive()) {
            return [
                'plan_name' => $sub->plan->name,
                'max_outlets' => (int) $sub->plan->max_outlets,
                'max_users' => (int) $sub->plan->max_users,
                'max_products' => (int) $sub->plan->max_products,
            ];
        }

        return [
            'plan_name' => 'Belum Berlangganan',
            'max_outlets' => 0,
            'max_users' => 0,
            'max_products' => 0,
        ];
    }

    public function hasSubscribedAppAccess(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $sub = $this->relationLoaded('currentSubscription')
            ? $this->currentSubscription
            : $this->currentSubscription()->first();

        if (!$sub || !$sub->isActive()) {
            return false;
        }

        return $this->service_type === 'template_saas'
            || $sub->status === 'active'
            || $this->clientLeads()->whereIn('status', ['live', 'monthly_service_active'])->exists();
    }

    /**
     * Normalized subscription expiry & lifecycle summary for dashboards and monitoring.
     */
    public function subscriptionSummary(): array
    {
        $sub = $this->relationLoaded('currentSubscription')
            ? $this->currentSubscription
            : $this->currentSubscription()->with('plan')->first();

        if ($sub) {
            $summary = $sub->toMonitoringSummary();
            $hasAccess = $this->hasSubscribedAppAccess();
            $summary['can_install_apk'] = $hasAccess;
            $summary['has_active_subscription'] = $hasAccess;
            return $summary;
        }

        return [
            'id' => null,
            'plan_id' => null,
            'plan_name' => 'Belum Berlangganan',
            'plan_slug' => 'none',
            'price_monthly' => 0,
            'status' => 'none',
            'lifecycle_status' => 'none',
            'starts_at' => null,
            'expires_at' => null,
            'starts_at_formatted' => '-',
            'expires_at_formatted' => 'Belum ada paket aktif',
            'days_remaining' => 0,
            'total_days' => 0,
            'progress_percent' => 0,
            'countdown_label' => 'Belum Aktif',
            'is_active' => false,
            'can_install_apk' => false,
            'has_active_subscription' => false,
            'is_expiring_soon' => false,
            'max_outlets' => 0,
            'max_users' => 0,
            'max_products' => 0,
        ];
    }

    /**
     * Activate, renew, or upgrade this business's template POS subscription atomically with fair proration across tier changes.
     */
    public function applySubscriptionPeriod(Plan $plan, int $days, string $status = 'active'): Subscription
    {
        return DB::transaction(function () use ($plan, $days, $status) {
            self::whereKey($this->id)->lockForUpdate()->first();

            $existing = Subscription::where('business_id', $this->id)
                ->with('plan')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $hasFutureExpiry = $existing && $existing->expires_at && $existing->expires_at->isFuture()
                && in_array($existing->status, ['active', 'trial'], true);

            $startsAt = now();
            $expiresAt = now()->copy()->addDays($days);

            if ($hasFutureExpiry) {
                $samePlan = (int) $existing->plan_id === (int) $plan->id;
                if ($samePlan || $existing->status === 'trial') {
                    // Same tier renewal or trial conversion preserves full unused future days
                    $startsAt = $existing->starts_at ?: now();
                    $expiresAt = $existing->expires_at->copy()->addDays($days);
                } else {
                    // Prorate remaining unused monetary balance across tier switches
                    $remainingDays = max(0, (int) now()->diffInDays($existing->expires_at, false));
                    $oldMonthly = max(1, (float) ($existing->plan?->price_monthly ?? 0));
                    $newMonthly = max(1, (float) $plan->price_monthly);
                    $proratedDays = (int) floor($remainingDays * ($oldMonthly / $newMonthly));
                    $startsAt = now();
                    $expiresAt = now()->copy()->addDays($days + $proratedDays);
                }
            }

            $subscription = Subscription::updateOrCreate(
                ['business_id' => $this->id],
                [
                    'plan_id' => $plan->id,
                    'status' => $status,
                    'starts_at' => $startsAt,
                    'expires_at' => $expiresAt,
                ]
            );

            $this->update([
                'service_type' => 'template_saas',
                'is_active' => true,
            ]);

            if ($this->outlets()->count() === 0) {
                Outlet::create([
                    'business_id' => $this->id,
                    'name' => 'Outlet Utama',
                    'address' => $this->address ?? 'Pusat',
                    'is_active' => true,
                ]);
            }

            return $subscription->load('plan');
        });
    }
}
