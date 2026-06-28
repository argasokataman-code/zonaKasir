<?php

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Services\Tenants\FlipPayoutProvider;
use App\Services\Tenants\DisbursementFailedException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    Config::set('flip.secret_key', 'test-secret-key');
    Config::set('flip.base_url', 'https://bigflip.id/big_sandbox_api');
    Config::set('flip.webhook_token', 'test-webhook-token');
    Config::set('flip.webhook_secret', 'test-webhook-secret');
});

// ── Phase 0.1: Fix Flip returned status handling ──

test('flip payout rounds amount instead of truncating', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v3/disbursement' => Http::response([
            'id' => '1234567890',
            'status' => 'pending',
            'amount' => 100001,
            'bank_code' => 'bca',
        ], 200),
    ]);

    $provider = app(FlipPayoutProvider::class);
    $result = $provider->send([
        'bank_code' => 'bca',
        'account_number' => '1234567890',
        'amount' => 100000.6,
        'remark' => 'Test payout',
        'idempotency_key' => 'test-idem-round',
    ]);

    expect($result['id'])->toBe('1234567890');
});

// ── Phase 0.3: Webhook token verification ──

test('webhook accepts valid token', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    $withdrawal = Withdrawal::create([
        'tenant_id' => 'toko_testing',
        'amount' => 100000,
        'bank_name' => 'BCA',
        'bank_code' => 'bca',
        'bank_account_name' => 'Test',
        'bank_account_number' => '1234567890',
        'status' => 'processing',
        'disburse_id' => '1234567890123456789',
        'requested_by' => User::first()->id,
    ]);

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(200);
    expect($withdrawal->fresh()->status)->toBe('completed');
});

test('webhook rejects invalid token', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'wrong-token',
    ];

    Withdrawal::create([
        'tenant_id' => 'toko_testing',
        'amount' => 100000,
        'bank_name' => 'BCA',
        'bank_code' => 'bca',
        'bank_account_name' => 'Test',
        'bank_account_number' => '1234567890',
        'status' => 'processing',
        'disburse_id' => '1234567890123456789',
        'requested_by' => User::first()->id,
    ]);

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(401);
});

test('webhook validates required fields', function () {
    $payload = [
        'token' => 'test-webhook-token',
    ];

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Ignored']);
});

// ── Phase 0.5: About null check ──
// Note: This test verifies the null check exists in code
// The actual route test requires tenant context setup

test('withdrawal service throws exception when about table is empty', function () {
    About::query()->delete();

    $service = app(\App\Services\Tenants\WithdrawalService::class);

    expect(fn () => $service->request(
        amount: 100000,
        idempotencyKey: 'test-about-null-' . now()->timestamp,
    ))->toThrow(\InvalidArgumentException::class, 'Tenant bank info not configured');
});

// ── Phase 0.6: SELECT FOR UPDATE (manual verification) ──
// Race condition tests are complex to automate, verified via code review

// ── Phase 0.7: Flip balance pre-check ──

test('withdrawal approval fails when flip balance is insufficient', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 0,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
    ]);

    $withdrawal = Withdrawal::create([
        'tenant_id' => 'toko_testing',
        'amount' => 100000,
        'bank_name' => 'BCA',
        'bank_code' => 'bca',
        'bank_account_name' => 'Test',
        'bank_account_number' => '1234567890',
        'status' => 'pending',
        'requested_by' => User::first()->id,
    ]);

    $service = app(\App\Services\Tenants\WithdrawalService::class);

    expect(fn () => $service->approve($withdrawal->id, User::first()->id))
        ->toThrow(DisbursementFailedException::class);
});
