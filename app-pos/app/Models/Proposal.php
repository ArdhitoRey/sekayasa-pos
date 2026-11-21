<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'lead_id',
        'proposal_number',
        'version',
        'development_cost',
        'monthly_maintenance_cost',
        'deliverables',
        'timeline_weeks',
        'status',
    ];

    protected $casts = [
        'development_cost' => 'decimal:2',
        'monthly_maintenance_cost' => 'decimal:2',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
