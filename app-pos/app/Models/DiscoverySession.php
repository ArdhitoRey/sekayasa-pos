<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoverySession extends Model
{
    protected $fillable = [
        'lead_id',
        'as_is_summary',
        'to_be_summary',
        'business_flow_notes',
        'target_hardware',
        'budget_estimation',
    ];

    protected $casts = [
        'budget_estimation' => 'decimal:2',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
