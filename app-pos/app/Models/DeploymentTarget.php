<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentTarget extends Model
{
    protected $fillable = [
        'lead_id',
        'environment',
        'provider',
        'host_url',
        'git_repo_url',
        'git_branch',
        'has_backup_schedule',
        'tls_ssl_active',
        'is_active',
    ];

    protected $casts = [
        'has_backup_schedule' => 'boolean',
        'tls_ssl_active' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
