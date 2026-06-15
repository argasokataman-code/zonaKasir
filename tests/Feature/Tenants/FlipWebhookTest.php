<?php

use App\Models\Tenants\User;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\About;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    $this->withdrawal = Withdrawal::create([
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
});

test('webhook updates withdrawal status to completed', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    Config::set('flip.webhook_token', 'test-webhook-token');

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(200);
    expect($this->withdrawal->fresh()->status)->toBe('completed');
});

test('webhook updates withdrawal status to failed on CANCELLED', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'CANCELLED',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    Config::set('flip.webhook_token', 'test-webhook-token');

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(200);
    expect($this->withdrawal->fresh()->status)->toBe('failed');
});

test('webhook rejects invalid token', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'wrong-token',
    ];

    Config::set('flip.webhook_token', 'test-webhook-token');

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(401);
});

test('webhook accepts unknown disbursement for retry', function () {
    $payload = [
        'id' => '9999999999999999999',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    Config::set('flip.webhook_token', 'test-webhook-token');

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(200);
});

test('webhook requires token', function () {
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
    ];

    Config::set('flip.webhook_token', 'test-webhook-token');

    $response = $this->postJson('/api/webhooks/flip', $payload);

    $response->assertStatus(401);
});
