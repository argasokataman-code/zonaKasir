<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * IdempotencyLog Model
 * 
 * Tracks idempotent API requests to prevent duplicate operations
 * Used for critical operations like payments and sales transactions
 */
class IdempotencyLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get logs for a specific endpoint
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope: Get completed operations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get failed operations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Clean up old logs (older than 24 hours)
     */
    public function scopeCleanOld($query, $hours = 24)
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }
}
