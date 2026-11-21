<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'business_id',
        'transaction_id',
        'user_id',
        'refund_amount',
        'reason',
        'refund_items',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_items' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
