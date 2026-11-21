<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UatTestCase extends Model
{
    protected $fillable = [
        'lead_id',
        'requirement_id',
        'test_title',
        'scenario',
        'expected_result',
        'status',
        'client_feedback',
        'tested_at',
    ];

    protected $casts = [
        'tested_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }

    public function requirement()
    {
        return $this->belongsTo(ProjectRequirement::class, 'requirement_id');
    }
}
