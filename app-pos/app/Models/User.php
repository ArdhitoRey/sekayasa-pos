<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'name',
        'email',
        'google_id',
        'avatar',
        'email_verified_at',
        'phone',
        'nik',
        'job_title',
        'password',
        'role', // superadmin, owner, admin, cashier
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    /**
     * Owner can always handle everything Admin handles (Admin is optional).
     */
    public function canManageOperations(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    /**
     * Exclusive to Business Owner (staff/outlet creation, billing, profit margins, legal profile).
     */
    public function canManageBusiness(): bool
    {
        return $this->role === 'owner';
    }

    public function activeShift()
    {
        return $this->hasOne(CashierShift::class)->where('status', 'open')->latest('opened_at');
    }
}
