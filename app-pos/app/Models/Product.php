<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'business_id',
        'category_id',
        'unit_id',
        'name',
        'sku',
        'barcode',
        'buy_price',
        'sell_price',
        'min_stock_alert',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'min_stock_alert' => 'integer',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockForOutlet($outletId)
    {
        return $this->hasOne(Inventory::class)->where('outlet_id', $outletId);
    }
}
