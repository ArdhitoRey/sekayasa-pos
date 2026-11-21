<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientLead extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'client_name',
        'business_name',
        'email',
        'phone',
        'business_type',
        'source',
        'status',
        'notes',
        'template_gap_notes',
        'preferred_consultation_at',
    ];

    protected $casts = [
        'preferred_consultation_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'lead_id');
    }

    public function discovery()
    {
        return $this->hasOne(DiscoverySession::class, 'lead_id');
    }

    public function requirements()
    {
        return $this->hasMany(ProjectRequirement::class, 'lead_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'lead_id');
    }

    public function currentProposal()
    {
        return $this->hasOne(Proposal::class, 'lead_id')->latest('version');
    }

    public function contracts()
    {
        return $this->hasMany(ProjectContract::class, 'lead_id');
    }

    public function uatCases()
    {
        return $this->hasMany(UatTestCase::class, 'lead_id');
    }

    public function deploymentTargets()
    {
        return $this->hasMany(DeploymentTarget::class, 'lead_id');
    }

    public function deploymentRuns()
    {
        return $this->hasMany(DeploymentRun::class, 'lead_id')->latest();
    }

    public function subscription()
    {
        return $this->hasOne(ServiceSubscription::class, 'lead_id')->latest();
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'lead_id');
    }
}
