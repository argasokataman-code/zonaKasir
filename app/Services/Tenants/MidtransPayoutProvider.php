<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransPayoutProvider implements DisbursementProvider
{
    public function send(array $params): array
    {
        $serverKey = config('midtrans.server_key');
        $environment = config('midtrans.environment', 'sandbox');

        $payload = [
            'bank_name' => $this->mapBankCode($params['bank_code'] ?? ''),
            'bank_account_name' => $params['account_name'] ?? '',
            'bank_account_number' => $params['account_number'] ?? '',
            'amount' => (int) ($params['amount'] ?? 0),
            'remark' => $params['remark'] ?? 'zonaKasir Disbursement',
            'email_to' => [],
            'mobile_to' => [],
            'idempotency_key' => $params['idempotency_key'] ?? uniqid('disburse-'),
        ];

        Log::info('Midtrans Disbursement request', [
            'payload' => $payload,
            'environment' => $environment,
        ]);

        // Sandbox: call Midtrans sandbox API for balance tracking
        if ($environment === 'sandbox') {
            return $this->handleSandbox($payload, $serverKey);
        }

        // Production: call real Midtrans Disbursement API
        return $this->handleProduction($payload, $serverKey);
    }

    public function status(string $disburseId): array
    {
        $serverKey = config('midtrans.server_key');
        $environment = config('midtrans.environment', 'sandbox');

        $url = $this->getDisbursementUrl($environment) . "/{$disburseId}/status";

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->get($url);

        if ($response->failed()) {
            return [
                'id' => $disburseId,
                'status' => 'unknown',
                'error' => $response->json('error_messages', ['Unknown error']),
            ];
        }

        return $response->json();
    }

    private function handleSandbox(array $payload, string $serverKey): array
    {
        Log::info('Midtrans Disbursement SANDBOX — calling sandbox API', [
            'payload' => $payload,
        ]);

        return $this->handleProduction($payload, $serverKey, config('midtrans.payout.sandbox_url'));
    }

    private function handleProduction(array $payload, string $serverKey, ?string $url = null): array
    {
        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url ?? config('midtrans.payout.url'), $payload);

        if ($response->failed()) {
            Log::error('Midtrans Disbursement failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Disbursement failed: ' . $this->getErrorMessage($response));
        }

        return $response->json();
    }

    private function mapBankCode(string $code): string
    {
        $map = [
            'BCA' => 'bca',
            'BRI' => 'bri',
            'BNI' => 'bni',
            'MANDIRI' => 'mandiri',
            'CIMB' => 'cimb',
            'BSI' => 'bni_syariah',
            'PERMATA' => 'permata',
            'DANAMON' => 'danamon',
            'PANIN' => 'panin',
            'MEGA' => 'mega',
            'BUKOPIN' => 'bukopin',
            'CITIBANK' => 'citibank',
            'HSBC' => 'hsbc',
            'STANDARD_CHARTERED' => 'standard_chartered',
        ];

        return $map[strtoupper($code)] ?? strtolower($code);
    }

    private function getErrorMessage($response): string
    {
        return $response->json('error_messages.0') ?? 'Unknown error';
    }

    private function getDisbursementUrl(string $environment): string
    {
        if ($environment === 'sandbox') {
            return config('midtrans.payout.sandbox_url');
        }

        return config('midtrans.payout.url');
    }
}
