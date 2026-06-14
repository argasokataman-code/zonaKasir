<?php

use App\Services\Tenants\FlipDataService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('flip.secret_key', 'test-secret-key');
    Config::set('flip.base_url', 'https://bigflip.id/big_sandbox_api');
});

test('flip data service returns balance', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 50000000,
            'pending_balance' => 1000000,
            'currency' => 'IDR',
        ], 200),
    ]);

    $balance = app(FlipDataService::class)->getBalance();

    expect($balance)->not->toBeNull();
    expect($balance['balance'])->toBe(50000000);
    expect($balance['pending_balance'])->toBe(1000000);
});

test('flip data service returns null on failed balance fetch', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([], 500),
    ]);

    $balance = app(FlipDataService::class)->getBalance();

    expect($balance)->toBeNull();
});

test('flip data service returns disbursements', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v3/disbursement*' => Http::response([
            'data' => [
                [
                    'id' => '123',
                    'bank_code' => 'bca',
                    'account_number' => '1234567890',
                    'amount' => 100000,
                    'status' => 'DONE',
                    'remark' => 'Test payout',
                    'created_at' => '2026-06-14 10:00:00',
                ],
                [
                    'id' => '456',
                    'bank_code' => 'bni',
                    'account_number' => '0987654321',
                    'amount' => 200000,
                    'status' => 'PENDING',
                    'remark' => 'Another payout',
                    'created_at' => '2026-06-14 11:00:00',
                ],
            ],
        ], 200),
    ]);

    $disbursements = app(FlipDataService::class)->getDisbursements();

    expect($disbursements)->toHaveCount(2);
    expect($disbursements[0]['status'])->toBe('DONE');
});

test('flip data service returns empty array on failed disbursement fetch', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v3/disbursement*' => Http::response([], 500),
    ]);

    $disbursements = app(FlipDataService::class)->getDisbursements();

    expect($disbursements)->toBe([]);
});
