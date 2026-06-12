<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnapBiQrisService
{
    private const SANDBOX_TOKEN_URL = 'https://api.sandbox.midtrans.com/v1.0/access-token/b2b-token';
    private const PRODUCTION_TOKEN_URL = 'https://api.midtrans.com/v1.0/access-token/b2b-token';
    private const SANDBOX_QRIS_URL = 'https://api.sandbox.midtrans.com/v4.0/debit/qr-merchant-presented-code/payment';
    private const PRODUCTION_QRIS_URL = 'https://api.midtrans.com/v4.0/debit/qr-merchant-presented-code/payment';

    /**
     * Create QRIS payment via SnapBi API, returns qr_string for QR code generation.
     */
    public function createPayment(Selling $selling): array
    {
        $config = config('midtrans.snapbi');
        $this->validateConfig($config);

        // 1. Get access token
        $accessToken = $this->getAccessToken($config);

        // 2. Create QRIS payment
        $orderId = 'Q-' . (int) (microtime(true) * 1000) . '-' . random_int(1000, 9999);
        $response = $this->createQrisPayment($config, $accessToken, $orderId, $selling);

        // 3. Create payment record
        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $orderId,
            'gross_amount' => $selling->total_price,
            'payment_type' => 'qris',
            'status' => 'pending',
        ]);

        return [
            'qr_string' => $response['qr_string'] ?? null,
            'qr_code_url' => $response['qr_code_url'] ?? null,
            'order_id' => $orderId,
            'payment_id' => $payment->id,
            'amount' => $selling->total_price,
        ];
    }

    private function getAccessToken(array $config): string
    {
        $url = config('midtrans.environment') === 'production'
            ? self::PRODUCTION_TOKEN_URL
            : self::SANDBOX_TOKEN_URL;

        // Generate JWT token for B2B access token request
        $jwt = $this->generateJwtToken($config['client_id'], $config['private_key']);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-CLIENT-KEY' => $config['client_id'],
            'Authorization' => 'Bearer ' . $jwt,
        ])->post($url, [
            'grantType' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error('SnapBi access token failed', ['status' => $response->status(), 'error' => $response->json()]);
            throw new \RuntimeException('Gagal mendapatkan access token SnapBi');
        }

        return $response->json('accessToken');
    }

    private function createQrisPayment(array $config, string $accessToken, string $orderId, Selling $selling): array
    {
        $url = config('midtrans.environment') === 'production'
            ? self::PRODUCTION_QRIS_URL
            : self::SANDBOX_QRIS_URL;

        // Generate HMAC signature for transaction header
        $timestamp = now()->toIso8601String();
        $signature = $this->generateHmacSignature($config['client_secret'], $orderId, (string) $selling->total_price, $timestamp);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'X-PARTNER-ID' => $config['partner_id'],
            'X-EXTERNAL-ID' => $orderId,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
        ])->post($url, [
            'partnerReferenceNo' => $orderId,
            'merchantId' => $config['merchant_id'],
            'amount' => [
                'value' => (string) $selling->total_price,
                'currency' => 'IDR',
            ],
            'validityPeriod' => now()->addMinutes(30)->toIso8601String(),
            'additionalInfo' => [
                'items' => $this->buildItems($selling),
            ],
        ]);

        if ($response->failed()) {
            Log::error('SnapBi QRIS payment failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat QRIS: ' . $this->getErrorMessage($response));
        }

        return $response->json();
    }

    private function generateJwtToken(string $clientId, string $privateKey): string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => $clientId,
            'iat' => time(),
            'exp' => time() + 3600,
        ]));

        $signatureInput = $header . '.' . $payload;
        openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');

        return $signatureInput . '.' . base64_encode($signature);
    }

    private function generateHmacSignature(string $clientSecret, string $orderId, string $amount, string $timestamp): string
    {
        $data = $orderId . $amount . $timestamp;
        return hash_hmac('sha512', $data, $clientSecret);
    }

    private function buildItems(Selling $selling): array
    {
        return $selling->sellingDetails->map(fn ($detail) => [
            'id' => (string) $detail->product_id,
            'price' => ['value' => (string) $detail->price, 'currency' => 'IDR'],
            'quantity' => $detail->qty,
            'name' => substr($detail->product?->name ?? 'Item', 0, 50),
            'brand' => 'ZonaKasir',
            'category' => 'POS',
            'merchantName' => $selling->about?->shop_name ?? 'ZonaKasir',
        ])->toArray();
    }

    private function validateConfig(array $config): void
    {
        $required = ['client_id', 'client_secret', 'private_key', 'partner_id', 'merchant_id'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \RuntimeException("SnapBi {$key} belum dikonfigurasi di .env");
            }
        }
    }

    private function getErrorMessage($response): string
    {
        return $response->json('statusMessage') ?? $response->json('error_messages.0') ?? 'Unknown error';
    }
}
