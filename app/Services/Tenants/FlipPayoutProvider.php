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

        $body = [
            'bank_code'      => $this->mapBankCode($params['bank_code']),
            'account_number' => $params['account_number'],
            'amount'         => (int) round($params['amount']),
            'remark'         => $params['remark'] ?? 'ZK Disbursement',
        ];
        if (! empty($params['account_name'])) {
            $body['account_name'] = $params['account_name'];
        }

        // Flip API V3 — idempotency-key di header, body form-urlencoded
        $response = Http::withBasicAuth($secretKey, '')
            ->withHeader('Idempotency-Key', $params['idempotency_key'])
            ->asForm()
            ->post($baseUrl . '/v3/disbursement', $body);

        if ($response->failed()) {
            Log::error('Flip Disbursement failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new DisbursementFailedException('Flip Disbursement failed: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json();

        Log::info('Flip disbursement success', [
            'id' => $data['id'] ?? $params['idempotency_key'],
            'status' => $data['status'] ?? 'unknown',
            'bank_code' => $params['bank_code'] ?? '?',
            'amount' => $params['amount'] ?? 0,
        ]);

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

        // Flip API V3 — GET /get-disbursement?id=xxx
        $response = Http::withBasicAuth($secretKey, '')
            ->get($baseUrl . '/v3/get-disbursement', ['id' => $disburseId]);

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
        // DB stores numeric codes (014, 009), Flip expects lowercase names (bca, bni)
        $map = [
            '014' => 'bca',
            '009' => 'bni',
            '002' => 'bri',
            '008' => 'mandiri',
            '022' => 'cimb',
            '451' => 'bsi',
            '200' => 'btn',
            '011' => 'danamon',
            '016' => 'maybank',
            '013' => 'permata',
            '028' => 'ocbc',
            '019' => 'panin',
            '426' => 'mega',
            '004' => 'bank_jatim',
            '006' => 'bank_jateng',
            '425' => 'bjb',
            '046' => 'bank_sumut',
        ];

        return $map[$code] ?? strtolower($code);
    }
}
