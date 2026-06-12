<?php

namespace App\Models\Tenants\Traits;

use App\Models\Tenants\IdempotencyLog;
use Illuminate\Support\Facades\DB;

trait IdempotentOperation
{
    /**
     * Check and mark idempotency via database (idempotency_logs table).
     * Returns cached result if already completed.
     */
    public function executeOnce(string $idempotencyKey, callable $callback): mixed
    {
        $log = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            ['status' => 'processing', 'endpoint' => request()->path(), 'method' => request()->method()]
        );

        if ($log->status === 'completed') {
            return $log->response ? json_decode($log->response, true) : null;
        }

        try {
            $result = DB::transaction($callback);

            $log->update([
                'status' => 'completed',
                'response' => json_encode($result),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'response' => json_encode(['error' => $e->getMessage()]),
            ]);
            throw $e;
        }
    }
}
