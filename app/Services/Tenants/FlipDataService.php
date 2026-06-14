<?php

namespace App\Services\Tenants;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlipDataService
{
    public function getBalance(): ?array
    {
        $secretKey = config('flip.secret_key');
        $baseUrl = config('flip.base_url');

        if (! $secretKey) {
            return null;
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->get($baseUrl . '/balance');

        if ($response->failed()) {
            Log::error('Flip: failed to fetch balance', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return null;
        }

        return $response->json();
    }

    public function getDisbursements(int $page = 1, int $perPage = 20): array
    {
        $secretKey = config('flip.secret_key');
        $baseUrl = config('flip.base_url');

        if (! $secretKey) {
            return [];
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->get($baseUrl . '/disbursements', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

        if ($response->failed()) {
            Log::error('Flip: failed to fetch disbursements', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return [];
        }

        return $response->json();
    }

    public function getDisbursement(string $id): ?array
    {
        $secretKey = config('flip.secret_key');
        $baseUrl = config('flip.base_url');

        if (! $secretKey) {
            return null;
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->get($baseUrl . '/disbursement/' . $id);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
