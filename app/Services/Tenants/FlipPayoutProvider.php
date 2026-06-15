<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlipPayoutProvider implements DisbursementProvider
{
    public function send(array $params): array
    {
        $secretKey = config('flip.secret_key');
        $baseUrl = config('flip.base_url');

        $response = Http::withBasicAuth($secretKey, '')
            ->post($baseUrl . '/v2/disbursement', [
                'bank_code'      => $this->mapBankCode($params['bank_code']),
                'account_number' => $params['account_number'],
                'amount'         => (int) round($params['amount']), // Round instead of truncate
                'remark'         => $params['remark'] ?? 'ZonaKasir Disbursement',
                'idempotency_key'=> $params['idempotency_key'],
            ]);

        if ($response->failed()) {
            Log::error('Flip Disbursement failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new DisbursementFailedException('Flip Disbursement failed: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json();

        return [
            'id' => $data['id'] ?? $params['idempotency_key'],
            'status' => $data['status'] ?? 'pending',
            'response' => $data,
        ];
    }

    public function status(string $disburseId): array
    {
        $secretKey = config('flip.secret_key');
        $baseUrl = config('flip.base_url');

        $response = Http::withBasicAuth($secretKey, '')
            ->get($baseUrl . '/v2/disbursement/' . $disburseId);

        if ($response->failed()) {
            return ['id' => $disburseId, 'status' => 'unknown', 'error' => $response->json()];
        }

        $data = $response->json();
        return [
            'id' => $disburseId,
            'status' => $data['status'] ?? 'unknown',
            'response' => $data,
        ];
    }

    private function mapBankCode(string $code): string
    {
        // Flip codes: bca, mandiri, bni, bri, cimb, etc.
        // We use simple mapping to lower case as most match the bank name
        return strtolower($code);
    }
}
