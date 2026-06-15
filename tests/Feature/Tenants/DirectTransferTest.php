<?php

use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\DirectTransferService;
use App\Services\Tenants\InsufficientBalanceException;
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

    // Update existing About record with bank info (created by RefreshDatabaseWithTenant)
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

test('direct transfer happy path', function () {
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
        notes: 'Test transfer'
    );

    expect($withdrawal->status)->toBe('completed');
    expect($withdrawal->type)->toBe('admin_direct');
    expect($withdrawal->amount)->toBe(97500.0); // Net amount (100000 - 2500 fee)
    expect((float) $withdrawal->fee_amount)->toBe(2500.0);
    expect($withdrawal->disburse_id)->toBe('1234567890123456789');
});

test('direct transfer fails when about table is empty', function () {
    About::query()->delete();

    $service = app(DirectTransferService::class);
    $admin = User::first();

    expect(fn () => $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
    ))->toThrow(\InvalidArgumentException::class, 'Tenant belum mengatur informasi bank');
});

test('direct transfer fails when bank info is incomplete', function () {
    About::query()->update([
        'bank_account_number' => null,
        'bank_code' => null,
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    expect(fn () => $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
    ))->toThrow(\InvalidArgumentException::class, 'Informasi bank tidak lengkap');
});

test('direct transfer fails when amount is below minimum', function () {
    $service = app(DirectTransferService::class);
    $admin = User::first();

    expect(fn () => $service->transferToTenant(
        amount: 40000,
        adminId: $admin->id,
    ))->toThrow(\InvalidArgumentException::class, 'Minimal transfer');
});

test('direct transfer fails when amount is above maximum', function () {
    $service = app(DirectTransferService::class);
    $admin = User::first();

    expect(fn () => $service->transferToTenant(
        amount: 60000000,
        adminId: $admin->id,
    ))->toThrow(\InvalidArgumentException::class, 'Maksimal transfer');
});

test('direct transfer fails when flip balance is insufficient', function () {
    Http::fake([
        'bigflip.id/big_sandbox_api/v2/general/balance' => Http::response([
            'balance' => 0,
            'pending_balance' => 0,
            'currency' => 'IDR',
        ], 200),
    ]);

    $service = app(DirectTransferService::class);
    $admin = User::first();

    expect(fn () => $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
    ))->toThrow(InsufficientBalanceException::class);
});

test('direct transfer fails when flip api returns error', function () {
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

    expect(fn () => $service->transferToTenant(
        amount: 100000,
        adminId: $admin->id,
    ))->toThrow(DisbursementFailedException::class);

    // Verify rollback
    $withdrawal = Withdrawal::where('type', 'admin_direct')->first();
    expect($withdrawal->status)->toBe('failed');
});

test('direct transfer creates correct ledger entries', function () {
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

    $ledgerEntries = \App\Models\Tenants\LedgerEntry::where('reference_type', 'transfer_to_tenant')
        ->where('reference_id', $withdrawal->id)
        ->get();

    expect($ledgerEntries)->toHaveCount(1);
    expect($ledgerEntries->first()->entry_type)->toBe('debit');
    expect($ledgerEntries->first()->amount)->toBe(100000.0); // Gross amount
});

test('direct transfer rollback on failure creates credit entry', function () {
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

    $withdrawal = Withdrawal::where('type', 'admin_direct')->first();

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
