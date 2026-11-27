<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Safely record an audit, operational, or error event without disrupting caller flow.
     */
    public static function record(
        ?int $businessId,
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $payload = [],
        ?string $ip = null
    ): void {
        try {
            static::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'payload' => $payload,
                'ip_address' => $ip ?? request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never break operational requests if audit logging fails
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
