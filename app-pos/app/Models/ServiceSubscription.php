<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSubscription extends Model
{
    protected $fillable = [
        'lead_id',
        'plan_tier',
        'monthly_fee',
        'starts_at',
        'next_billing_at',
        'status',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'starts_at' => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
