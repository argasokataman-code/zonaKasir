<?php

use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\DirectTransferService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TransferReceived;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    Config::set('flip.secret_key', 'test-secret-key');
    Config::set('flip.base_url', 'https://bigflip.id/big_sandbox_api');
    Config::set('flip.webhook_token', 'test-webhook-token');
    Config::set('flip.webhook_secret', 'test-webhook-secret');

    // Update existing About record with bank info
    About::query()->update([
        'bank_name' => 'BCA',
        'bank_code' => 'bca',
        'bank_account_name' => 'PT Toko Testing',
        'bank_account_number' => '1234567890',
    ]);

    // Create initial credit entry to give ledger balance
    \App\Models\Tenants\LedgerEntry::create([
        'ledgerable_type' => \App\Models\Tenants\Selling::class,
        'ledgerable_id' => 1,
        'entry_type' => 'credit',
        'amount' => 10000000,
        'balance_before' => 0,
        'balance_after' => 10000000,
        'description' => 'Initial test balance',
        'reference_type' => 'test',
        'reference_id' => 1,
    ]);
});

test('full transfer flow: admin submit → Flip API → webhook → notification', function () {
    Notification::fake();

    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 10000000,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
        'bigflip.id/big_sandbox_api/v2/disbursement' => Http::response([
            'id' => '1234567890123456789',
            'status' => 'pending',
            'amount' => 100000,
            'bank_code' => 'bca',
        ], 200),
    ]);

    // Step 1: Admin submits transfer
    $service = app(DirectTransferService::class);
    $admin = User::first();

    $withdrawal = $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
        notes: 'Integration test'
    );

    expect($withdrawal->status)->toBe('processing');
    expect($withdrawal->type)->toBe('admin_direct');
    expect($withdrawal->disburse_id)->toBe('1234567890123456789');

    // Step 2: Webhook received
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    $response = $this->postJson('/api/webhooks/flip', $payload);
    $response->assertStatus(200);

    // Step 3: Verify status updated
    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('completed');

    // Step 4: Verify notification sent
    Notification::assertSentTo(
        $withdrawal->requestedBy,
        TransferReceived::class
    );
});

test('full transfer flow: admin submit → Flip API fail → rollback', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 10000000,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
        'bigflip.id/big_sandbox_api/v2/disbursement' => Http::response([
            'message' => 'Insufficient balance',
        ], 400),
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    try {
        $service->transferToTenant(
            amount: 100000,
            adminId: $admin->id,
        );
    } catch (\Throwable $e) {
        // Expected
    }

    // Verify withdrawal created and failed
    $withdrawal = Withdrawal::where('type', 'admin_direct')->first();
    expect($withdrawal)->not->toBeNull();
    expect($withdrawal->status)->toBe('failed');

    // Verify ledger rollback
    $debitEntry = \App\Models\Tenants\LedgerEntry::where('reference_type', 'transfer_to_tenant')
        ->where('reference_id', $withdrawal->id)
        ->where('entry_type', 'debit')
        ->first();

    $creditEntry = \App\Models\Tenants\LedgerEntry::where('reference_type', 'transfer_rollback')
        ->where('reference_id', $withdrawal->id)
        ->where('entry_type', 'credit')
        ->first();

    expect($debitEntry)->not->toBeNull();
    expect($creditEntry)->not->toBeNull();
    expect($debitEntry->amount)->toBe($creditEntry->amount);
});

test('webhook is idempotent - duplicate webhook does not change status', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 10000000,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
        'bigflip.id/big_sandbox_api/v2/disbursement' => Http::response([
            'id' => '1234567890123456789',
            'status' => 'DONE',
            'amount' => 100000,
            'bank_code' => 'bca',
        ], 200),
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    $withdrawal = $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
    );

    // First webhook
    $payload = [
        'id' => '1234567890123456789',
        'status' => 'DONE',
        'amount' => '100000',
        'token' => 'test-webhook-token',
    ];

    $this->postJson('/api/webhooks/flip', $payload)->assertStatus(200);

    // Second webhook (duplicate)
    $this->postJson('/api/webhooks/flip', $payload)->assertStatus(200);

    // Status should still be completed
    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('completed');
});

test('transfer minimum amount works', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 10000000,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
        'bigflip.id/big_sandbox_api/v2/disbursement' => Http::response([
            'id' => '1234567890123456789',
            'status' => 'DONE',
            'amount' => 50000,
            'bank_code' => 'bca',
        ], 200),
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    $withdrawal = $service->transferToTenant(
        amount: 50000,
        adminId: $admin->id,
    );

    expect($withdrawal->status)->toBe('completed');
    expect($withdrawal->amount)->toBe(47500.0); // 50000 - 2500 fee
});

test('multiple transfers to same tenant work', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 10000000,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
        'bigflip.id/big_sandbox_api/v2/disbursement' => Http::response([
            'id' => '1234567890123456789',
            'status' => 'DONE',
            'amount' => 100000,
            'bank_code' => 'bca',
        ], 200),
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    // First transfer
    $withdrawal1 = $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
        notes: 'First transfer',
    );

    // Second transfer
    $withdrawal2 = $service->transferToTenant(
        amount: 200000,
        adminId: $admin->id,
        notes: 'Second transfer',
    );

    expect($withdrawal1->id)->not->toBe($withdrawal2->id);
    expect($withdrawal1->status)->toBe('completed');
    expect($withdrawal2->status)->toBe('completed');
});
