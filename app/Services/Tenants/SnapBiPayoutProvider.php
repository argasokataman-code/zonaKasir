<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SnapBiPayoutProvider implements DisbursementProvider
{
    private function getAccessToken(): string
    {
        return Cache::remember('midtrans_snapbi_token', 3600, function () {
            $config = config('midtrans.snapbi');
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.sandbox.midtrans.com/snapbi/v1.0/access-token', [
                'clientId' => $config['client_id'],
                'clientSecret' => $config['client_secret'],
            ]);

            if ($response->failed()) {
                Log::error('SnapBI Access Token failed', ['error' => $response->json()]);
                throw new \RuntimeException('Gagal mendapatkan Access Token SnapBI');
            }

            return $response->json('accessToken');
        });
    }

    private function generateSignature(string $timestamp, string $path, string $payload, string $clientSecret): string
    {
        $stringToSign = "POST:" . $path . ":" . $timestamp . ":" . $payload;
        return base64_encode(hash_hmac('sha256', $stringToSign, $clientSecret, true));
    }

    public function send(array $params): array
    {
        $config = config('midtrans.snapbi');
        $environment = config('midtrans.environment', 'sandbox');
        $baseUrl = $environment === 'sandbox' 
            ? 'https://api.sandbox.midtrans.com/snapbi/v1.0' 
            : 'https://api.midtrans.com/snapbi/v1.0';

        $token = $this->getAccessToken();
        $path = '/disbursement';
        
        $payload = [
            'partnerReferenceNo' => $params['idempotency_key'],
            'amount' => [
                'value' => number_format((float)$params['amount'], 2, '.', ''),
                'currency' => 'IDR',
            ],
            'beneficiaryAccountNo' => $params['account_number'],
            'beneficiaryBankCode' => $params['bank_code'],
            'beneficiaryName' => $params['account_name'],
            'remark' => $params['remark'] ?? 'Zonakasir Disbursement',
        ];

        $jsonPayload = json_encode($payload);
        $timestamp = now()->toIso8601String();
        $signature = $this->generateSignature($timestamp, $path, $jsonPayload, $config['client_secret']);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-SIGNATURE' => $signature,
            'X-TIMESTAMP' => $timestamp,
            'X-PARTNER-ID' => $config['partner_id'],
            'X-EXTERNAL-ID' => $params['idempotency_key'],
            'CHANNEL-ID' => $config['channel_id'] ?? 'ZONAKASIR',
            'Content-Type' => 'application/json',
        ])->post($baseUrl . $path, $payload);

        if ($response->failed()) {
            Log::error('SnapBI Disbursement failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new DisbursementFailedException('SnapBI Disbursement failed: ' . ($response->json('responseMessage') ?? 'Unknown error'));
        }

        $data = $response->json();

        return [
            'id' => $data['additionalInfo']['referenceNo'] ?? $params['idempotency_key'],
            'status' => $data['status'] ?? 'success',
            'response' => $data,
        ];
    }

    public function status(string $disburseId): array
    {
        $config = config('midtrans.snapbi');
        $environment = config('midtrans.environment', 'sandbox');
        $baseUrl = $environment === 'sandbox' 
            ? 'https://api.sandbox.midtrans.com/snapbi/v1.0' 
            : 'https://api.midtrans.com/snapbi/v1.0';

        $token = $this->getAccessToken();
        $path = '/transaction-history-list';

        // The status endpoint for SnapBI is transaction-history-list
        // You need to send a POST request with specific body to get the status
        $payload = [
            'partnerReferenceNo' => $disburseId,
            'additionalInfo' => [
                'types' => ['DISBURSEMENT'],
            ]
        ];

        $jsonPayload = json_encode($payload);
        $timestamp = now()->toIso8601String();
        $signature = $this->generateSignature($timestamp, $path, $jsonPayload, $config['client_secret']);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-SIGNATURE' => $signature,
            'X-TIMESTAMP' => $timestamp,
            'X-PARTNER-ID' => $config['partner_id'],
            'CHANNEL-ID' => $config['channel_id'] ?? 'ZONAKASIR',
            'Content-Type' => 'application/json',
        ])->post($baseUrl . $path, $payload);

        if ($response->failed()) {
            return ['id' => $disburseId, 'status' => 'unknown', 'error' => $response->json()];
        }

        $history = $response->json('detailData.0');
        if (!$history) {
            return ['id' => $disburseId, 'status' => 'not_found'];
        }

        return [
            'id' => $disburseId,
            'status' => $history['additionalInfo']['status'] ?? 'unknown',
            'response' => $history,
        ];
    }
}
