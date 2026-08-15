<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class LicenseService
{
    private const CACHE_KEY = 'onprem_license_verify';

    private const CACHE_TTL = 86400;

    public function sign(array $payload, string $privateKeyPem): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        openssl_sign($json, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);

        return base64_encode($json).'.'.base64_encode($signature);
    }

    public function verify(string $license, string $domain, ?string $publicKeyPem = null): array
    {
        $publicKeyPem = $publicKeyPem ?? config('onprem.license.public_key');

        if (! $publicKeyPem || ! str_starts_with($license, 'ZONA-')) {
            return $this->fail('Invalid license format');
        }

        // strip "ZONA-{slug}-" human-readable prefix; base64 never contains "." or "-"
        $rest = substr($license, 5);
        [$payloadAndSlug, $signatureB64] = explode('.', $rest, 2);

        $pos = strrpos($payloadAndSlug, '-');
        $payloadB64 = $pos === false ? $payloadAndSlug : substr($payloadAndSlug, $pos + 1);

        $json = base64_decode($payloadB64, true);
        $signature = base64_decode($signatureB64, true);

        if ($json === false || $signature === false) {
            return $this->fail('Corrupted license data');
        }

        if (openssl_verify($json, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256) !== 1) {
            return $this->fail('Invalid signature');
        }

        $payload = json_decode($json, true);

        if (($payload['domain'] ?? null) !== $domain) {
            return $this->fail('License is bound to another domain');
        }

        $expiresAt = $payload['expires_at'] ?? null;
        if ($expiresAt && strtotime($expiresAt) < time()) {
            return $this->fail('License expired');
        }

        return [
            'valid' => true,
            'payload' => $payload,
        ];
    }

    public function verifyCached(string $license, string $domain): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($license, $domain) {
            return $this->verify($license, $domain);
        });
    }

    private function fail(string $reason): array
    {
        return [
            'valid' => false,
            'reason' => $reason,
        ];
    }
}
