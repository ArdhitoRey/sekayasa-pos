<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentRun extends Model
{
    protected $fillable = [
        'lead_id',
        'target_id',
        'environment',
        'release_version',
        'commit_sha',
        'operator_name',
        'status',
        'log_output',
        'backup_reference',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }

    public function target()
    {
        return $this->belongsTo(DeploymentTarget::class, 'target_id');
    }
}
