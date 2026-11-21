<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'lead_id',
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
    ];

    public function lead()
    {
        return $this->belongsTo(ClientLead::class, 'lead_id');
    }
}
