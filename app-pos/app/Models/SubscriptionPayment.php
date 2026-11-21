<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'business_id',
        'subscription_id',
        'plan_id',
        'invoice_number',
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
        'expires_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'unique_code' => 'integer',
        'webhook_payload' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

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
}
