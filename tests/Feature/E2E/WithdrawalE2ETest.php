<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\About;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Selling;
use App\Models\Tenants\User;
use App\Services\Tenants\MidtransFeeCalculator;
use App\Services\Tenants\LedgerService;
use App\Services\Tenants\WithdrawalService;
use App\Models\Tenants\Withdrawal;
use Illuminate\Support\Facades\App;
use Tests\Mocks\MockDisbursementProvider;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Withdrawal E2E Flow', function () {
    beforeEach(function () {
        App::bind(\App\Services\Tenants\DisbursementProvider::class, MockDisbursementProvider::class);
        $this->user = User::first();
        $this->about = About::first();
        if ($this->about) {
            $this->about->update([
                'bank_name' => 'BCA',
                'bank_account_name' => 'Test Tenant',
                'bank_account_number' => '1234567890',
                'bank_code' => '014',
            ]);
        }
        // seed digital payment method
        PaymentMethod::firstOrCreate(
            ['name' => 'GoPay'],
            ['is_cash' => false, 'is_wallet' => true, 'icon' => 'gopay.png']
        );
    });

    it('balance starts at 0', function () {
        $balance = app(LedgerService::class)->getCurrentBalance();
        expect($balance)->toBe(0.0);
    });

    it('cannot withdraw with 0 balance', function () {
        $this->actingAs($this->user);
        try {
            app(WithdrawalService::class)->request(
                amount: 50000,
                idempotencyKey: 'test-wd-' . uniqid(),
            );
            $this->fail('Should have thrown InsufficientBalanceException');
        } catch (\App\Services\Tenants\InsufficientBalanceException $e) {
            expect($e->getMessage())->toContain('Insufficient balance. Available');
        }
    });

    it('fee calculator returns correct values for credit card', function () {
        $fees = (new MidtransFeeCalculator)->calculate('credit_card', 100000, 1.0);
        expect($fees['fee_midtrans'])->toBe(2950.0);
        expect($fees['fee_platform'])->toBe(1000.0);
        expect($fees['net_amount'])->toBe(96050.0);
    });

    it('fee calculator returns correct values for bank transfer', function () {
        $fees = (new MidtransFeeCalculator)->calculate('bank_transfer', 50000, 1.0);
        expect($fees['fee_midtrans'])->toBe(2500.0);
        expect($fees['fee_platform'])->toBe(500.0);
        expect($fees['net_amount'])->toBe(47000.0);
    });

    it('fee calculator throws on unknown payment type', function () {
        try {
            (new MidtransFeeCalculator)->calculate('unknown', 50000, 1.0);
            $this->fail('Should have thrown');
        } catch (\App\Services\Tenants\UnknownPaymentTypeException $e) {
            expect($e->getMessage())->toContain('Unknown payment type');
        }
    });

    it('withdrawal request requires idempotency key', function () {
        $this->actingAs($this->user);
        try {
            app(WithdrawalService::class)->request(amount: 50000, idempotencyKey: '');
            $this->fail('Should have thrown');
        } catch (\InvalidArgumentException $e) {
            expect($e->getMessage())->toContain('idempotency_key is required');
        }
    });

    it('withdrawal creates record with correct status', function () {
        // simulate balance by creating a ledger entry directly
        app(LedgerService::class)->entry(
            ledgerableType: Selling::class,
            ledgerableId: 1,
            entryType: 'credit',
            amount: 100000,
            description: 'test credit',
            referenceType: 'selling',
            referenceId: 1,
        );

        $this->actingAs($this->user);
        $withdrawal = app(WithdrawalService::class)->request(
            amount: 50000,
            idempotencyKey: 'test-wd-' . uniqid(),
        );

        expect($withdrawal)->toBeInstanceOf(Withdrawal::class);
        expect($withdrawal->amount)->toBe(50000.0);
        expect($withdrawal->bank_name)->toBe('BCA');
        expect($withdrawal->status)->toBeIn(['pending', 'approved']);
    });

    it('withdrawal reduces balance', function () {
        app(LedgerService::class)->entry(
            ledgerableType: Selling::class,
            ledgerableId: 2,
            entryType: 'credit',
            amount: 100000,
            description: 'test credit 2',
            referenceType: 'selling',
            referenceId: 2,
        );

        $this->actingAs($this->user);
        $balanceBefore = app(LedgerService::class)->getCurrentBalance();
        app(WithdrawalService::class)->request(
            amount: 30000,
            idempotencyKey: 'test-wd-balance-' . uniqid(),
        );
        $balanceAfter = app(LedgerService::class)->getCurrentBalance();

        expect($balanceBefore - $balanceAfter)->toBe(30000.0);
    });

    it('reject withdrawal restores balance', function () {
        app(LedgerService::class)->entry(
            ledgerableType: Selling::class,
            ledgerableId: 3,
            entryType: 'credit',
            amount: 100000,
            description: 'test credit 3',
            referenceType: 'selling',
            referenceId: 3,
        );

        $this->actingAs($this->user);
        $balanceBefore = app(LedgerService::class)->getCurrentBalance();

        $withdrawal = app(WithdrawalService::class)->request(
            amount: 40000,
            idempotencyKey: 'test-wd-reject-' . uniqid(),
        );

        app(WithdrawalService::class)->reject($withdrawal->id, $this->user->id, 'Test reject');

        $balanceAfter = app(LedgerService::class)->getCurrentBalance();
        expect($balanceAfter)->toBe($balanceBefore); // should be restored
    });

    it('exists table structure matches migration', function () {
        expect(\Illuminate\Support\Facades\Schema::hasTable('withdrawals'))->toBeTrue();
        expect(\Illuminate\Support\Facades\Schema::hasTable('ledger_entries'))->toBeTrue();
        expect(\Illuminate\Support\Facades\Schema::hasTable('midtrans_payments'))->toBeTrue();
        expect(\Illuminate\Support\Facades\Schema::hasTable('settlements'))->toBeTrue();
        expect(\Illuminate\Support\Facades\Schema::hasColumns('withdrawals', [
            'id', 'amount', 'bank_name', 'bank_account_name', 'bank_account_number',
            'bank_code', 'status', 'idempotency_key', 'disburse_id',
            'requested_by', 'approved_by', 'rejected_by', 'rejection_reason',
            'processed_at', 'created_at', 'updated_at',
        ]))->toBeTrue();
    });

    it('midtrans payments table schema correct', function () {
        expect(\Illuminate\Support\Facades\Schema::hasColumns('midtrans_payments', [
            'selling_id', 'order_id', 'gross_amount', 'payment_type',
            'status', 'fee_midtrans', 'fee_platform', 'net_amount', 'paid_at',
        ]))->toBeTrue();
    });
});
