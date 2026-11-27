<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'outlet_code',
        'address',
        'city',
        'phone',
        'receipt_header',
        'receipt_footer',
        'tax_percentage',
        'feature_overrides',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tax_percentage' => 'float',
        'feature_overrides' => 'array',
    ];

    public function effectiveTaxPercentage(?Business $business = null): float
    {
        if ($this->tax_percentage !== null) {
            return (float) $this->tax_percentage;
        }
        $biz = $business ?? $this->business;
        return $biz ? (float) ($biz->tax_percentage ?? 0) : 0.0;
    }

    public function effectiveReceiptFooter(?Business $business = null): ?string
    {
        if (!empty($this->receipt_footer)) {
            return $this->receipt_footer;
        }
        $biz = $business ?? $this->business;
        return $biz?->receipt_footer;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class);
    }
}
