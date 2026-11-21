<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'logo',
        'currency',
        'timezone',
        'tax_percentage',
        'receipt_footer',
        'is_active',
        'service_type',
    ];

    protected $casts = [
        'tax_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function outlets()
    {
        return $this->hasMany(Outlet::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latest('id');
    }
}
