<?php

use App\Services\Tenants\FlipPayoutProvider;
use App\Services\Tenants\DisbursementFailedException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('flip.secret_key', 'test-secret-key');
    Config::set('flip.base_url', 'https://big.flip.id/api/v2');
});

test('flip payout sends disbursement successfully', function () {
    Http::fake([
        'big.flip.id/api/v2/disbursement' => Http::response([
            'id' => '1234567890',
            'status' => 'pending',
            'amount' => 100000,
            'bank_code' => 'bca',
        ], 200),
    ]);

    $provider = app(FlipPayoutProvider::class);
    $result = $provider->send([
        'bank_code' => 'bca',
        'account_number' => '1234567890',
        'amount' => 100000,
        'remark' => 'Test payout',
        'idempotency_key' => 'test-idem-1',
    ]);

    expect($result['id'])->toBe('1234567890');
    expect($result['status'])->toBe('pending');
});

test('flip payout throws exception on failure', function () {
    Http::fake([
        'big.flip.id/api/v2/disbursement' => Http::response([
            'message' => 'Insufficient balance',
        ], 400),
    ]);

    $provider = app(FlipPayoutProvider::class);

    expect(fn () => $provider->send([
        'bank_code' => 'bca',
        'account_number' => '1234567890',
        'amount' => 1000000000,
        'remark' => 'Too large payout',
        'idempotency_key' => 'test-idem-2',
    ]))->toThrow(DisbursementFailedException::class);
});

test('flip payout checks status', function () {
    Http::fake([
        'big.flip.id/api/v2/disbursement/123' => Http::response([
            'id' => '123',
            'status' => 'DONE',
            'amount' => 100000,
        ], 200),
    ]);

    $provider = app(FlipPayoutProvider::class);
    $result = $provider->status('123');

    expect($result['id'])->toBe('123');
    expect($result['status'])->toBe('DONE');
});
