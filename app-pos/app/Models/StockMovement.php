<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'business_id',
        'outlet_id',
        'product_id',
        'type', // sale, purchase_in, adjustment_add, adjustment_sub, transfer_in, transfer_out, refund, void
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reason',
        'user_id',
        'reference_type',
        'reference_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
