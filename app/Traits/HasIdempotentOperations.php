<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait to enable idempotent API operations
 * 
 * Prevents duplicate operations if requests are retried
 * Uses idempotency_key from request headers
 */
trait HasIdempotentOperations
{
    /**
     * Track idempotent operation results
     * Stores the result of an operation by idempotency key
     * 
     * @param string $key Unique idempotency key (UUID recommended)
     * @param callable $callback Operation to execute
     * @return mixed Cached or new operation result
     */
    public function executeIdempotently(string $key, callable $callback)
    {
        // In production, use Cache::remember with longer TTL (24 hours typical)
        // For now, store in database via IdempotencyLog model
        
        $log = \App\Models\Tenants\IdempotencyLog::select('id', 'idempotency_key', 'status', 'response')->where('idempotency_key', $key)->first();
        
        if ($log && $log->status === 'completed') {
            // Return cached successful result
            return json_decode($log->response, true);
        }
        
        if ($log && $log->status === 'processing') {
            // Operation still in progress, ask client to retry
            abort(409, 'Operation in progress. Please retry after a moment.');
        }
        
        // Create processing log
        $log = \App\Models\Tenants\IdempotencyLog::create([
            'idempotency_key' => $key,
            'status' => 'processing',
            'endpoint' => request()->getPathInfo(),
            'method' => request()->getMethod(),
        ]);
        
        try {
            $result = $callback();
            
            // Store successful result
            $log->update([
                'status' => 'completed',
                'response' => json_encode($result),
            ]);
            
            return $result;
        } catch (\Exception $e) {
            // Store failed result
            $log->update([
                'status' => 'failed',
                'response' => json_encode(['error' => $e->getMessage()]),
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate unique idempotency key if not provided
     */
    public static function generateIdempotencyKey(): string
    {
        return request()->header('Idempotency-Key') ?? Str::uuid();
    }
}
