<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Log;

class LogDisbursementProvider implements DisbursementProvider
{
    public function send(array $params): array
    {
        Log::info('Disbursement requested (no provider configured)', [
            'bank_code' => $params['bank_code'] ?? '',
            'account_number' => $params['account_number'] ?? '',
            'amount' => $params['amount'] ?? 0,
            'remark' => $params['remark'] ?? '',
        ]);

        return [
            'id' => 'LOG-' . uniqid(),
            'status' => 'completed',
            'message' => 'Logged (no real disbursement provider configured)',
        ];
    }

    public function status(string $disburseId): array
    {
        return [
            'id' => $disburseId,
            'status' => 'completed',
        ];
    }
}
