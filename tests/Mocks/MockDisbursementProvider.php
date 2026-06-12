<?php

namespace Tests\Mocks;

use App\Services\Tenants\DisbursementProvider;

class MockDisbursementProvider implements DisbursementProvider
{
    public function send(array $params): array
    {
        return [
            'id' => 'mock-' . uniqid(),
            'status' => 'completed',
            'amount' => $params['amount'],
            'bank_code' => $params['bank_code'],
            'account_number' => $params['account_number'],
            'account_name' => $params['account_name'],
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
