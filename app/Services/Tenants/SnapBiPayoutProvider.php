<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Migrated to FlipPayoutProvider.
 *             Kept as reference — not active. Binding in AppServiceProvider
 *             uses FlipPayoutProvider instead.
 */
class SnapBiPayoutProvider implements DisbursementProvider
{
    private const SNAP_BI_SANDBOX_BASE_URL = 'https://merchants.sbx.midtrans.com';
    private const SNAP_BI_PRODUCTION_BASE_URL = 'https://merchants.midtrans.com';
    private const ACCESS_TOKEN_PATH = '/v1.0/access-token/b2b';

    private ?string $cachedToken = null;

    private function getBaseUrl(): string
    {
        return config('midtrans.environment', 'sandbox') === 'production'
            ? self::SNAP_BI_PRODUCTION_BASE_URL
            : self::SNAP_BI_SANDBOX_BASE_URL;
    }

    private function generateAsymmetricSignature(string $clientId, string $timestamp, string $privateKey): string
    {
        $stringToSign = $clientId . '|' . $timestamp;
        $binarySignature = null;
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($binarySignature);
    }

    private function generateSymmetricSignature(
        string $accessToken,
        array $requestBody,
        string $method,
        string $path,
        string $clientSecret,
        string $timestamp
    ): string {
        $minifiedBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = hash('sha256', $minifiedBody, true);
        $hexEncodedHash = bin2hex($hashedBody);
        $lowercaseHexHash = strtolower($hexEncodedHash);

        $payload = strtoupper($method) . ':' . $path . ':' . $accessToken . ':' . $lowercaseHexHash . ':' . $timestamp;
        $hmac = hash_hmac('sha512', $payload, $clientSecret, true);

        return base64_encode($hmac);
    }

    private function getAccessToken(): string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        $config = config('midtrans.snapbi');
        $timestamp = now()->toIso8601String();
        $signature = $this->generateAsymmetricSignature($config['client_id'], $timestamp, $config['private_key']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-CLIENT-KEY' => $config['client_id'],
            'X-SIGNATURE' => $signature,
            'X-TIMESTAMP' => $timestamp,
        ])->post($this->getBaseUrl() . self::ACCESS_TOKEN_PATH, [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error('SnapBI Access Token failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal mendapatkan Access Token SnapBI: ' . ($response->json('error_description') ?? $response->body()));
        }

        $this->cachedToken = $response->json('accessToken');

        return $this->cachedToken;
    }

    public function send(array $params): array
    {
        $config = config('midtrans.snapbi');
        $baseUrl = $this->getBaseUrl();
        $path = '/v1.0/disbursement';
        $token = $this->getAccessToken();
        $timestamp = now()->toIso8601String();

        $payload = [
            'partnerReferenceNo' => $params['idempotency_key'],
            'amount' => [
                'value' => number_format((float) $params['amount'], 2, '.', ''),
                'currency' => 'IDR',
            ],
            'beneficiaryAccountNo' => $params['account_number'],
            'beneficiaryBankCode' => $params['bank_code'],
            'beneficiaryName' => $params['account_name'],
            'remark' => $params['remark'] ?? 'Zonakasir Disbursement',
        ];

        $signature = $this->generateSymmetricSignature(
            $token, $payload, 'post', $path,
            $config['client_secret'], $timestamp
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'X-PARTNER-ID' => $config['partner_id'],
            'X-EXTERNAL-ID' => $params['idempotency_key'],
            'CHANNEL-ID' => $config['channel_id'] ?? 'ZONAKASIR',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
        ])->post($baseUrl . $path, $payload);

        if ($response->failed()) {
            Log::error('SnapBI Disbursement failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new DisbursementFailedException(
                'SnapBI Disbursement failed: ' . ($response->json('responseMessage') ?? $response->body())
            );
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
        $baseUrl = $this->getBaseUrl();
        $path = '/v1.0/disbursement/status';
        $token = $this->getAccessToken();
        $timestamp = now()->toIso8601String();

        $payload = [
            'partnerReferenceNo' => $disburseId,
        ];

        $signature = $this->generateSymmetricSignature(
            $token, $payload, 'post', $path,
            $config['client_secret'], $timestamp
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'X-PARTNER-ID' => $config['partner_id'],
            'X-EXTERNAL-ID' => $disburseId,
            'CHANNEL-ID' => $config['channel_id'] ?? 'ZONAKASIR',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
        ])->post($baseUrl . $path, $payload);

        if ($response->failed()) {
            return ['id' => $disburseId, 'status' => 'unknown', 'error' => $response->json()];
        }

        return [
            'id' => $disburseId,
            'status' => $response->json('status') ?? 'unknown',
            'response' => $response->json(),
        ];
    }
}
