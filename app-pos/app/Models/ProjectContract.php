<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectContract extends Model
{
    protected $fillable = [
        'lead_id',
        'proposal_id',
        'contract_number',
        'type',
        'total_amount',
        'is_signed',
        'signed_at',
        'payment_status',
        'payment_verified_at',
        'payment_reference',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
