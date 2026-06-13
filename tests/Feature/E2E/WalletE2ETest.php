<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\WalletTransaction;
use App\Services\Tenants\WalletService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Wallet System', function () {
    it('topUp increases member wallet balance', function () {
        $member = Member::factory()->create(['wallet_balance' => 0]);
        $service = new WalletService();

        $service->topUp($member, 50000, 'Initial deposit');
        $member->refresh();

        expect($member->wallet_balance)->toBe(50000);
        expect(WalletTransaction::where('member_id', $member->id)->count())->toBe(1);
    });

    it('topUp throws when amount is zero or negative', function () {
        $member = Member::factory()->create();
        $service = new WalletService();

        $service->topUp($member, 0);
    })->throws(\InvalidArgumentException::class);

    it('pay deducts from member wallet balance', function () {
        $member = Member::factory()->create(['wallet_balance' => 100000]);
        $selling = Selling::factory()->create(['member_id' => $member->id]);
        $service = new WalletService();

        $service->pay($member, 30000, $selling);
        $member->refresh();

        expect($member->wallet_balance)->toBe(70000);
    });

    it('pay throws when insufficient balance', function () {
        $member = Member::factory()->create(['wallet_balance' => 10000]);
        $selling = Selling::factory()->create(['member_id' => $member->id]);
        $service = new WalletService();

        $service->pay($member, 50000, $selling);
    })->throws(\InvalidArgumentException::class);

    it('getBalance returns current wallet balance', function () {
        $member = Member::factory()->create(['wallet_balance' => 75000]);
        $service = new WalletService();

        expect($service->getBalance($member))->toBe(75000);
    });

    it('wallet transaction records are created with correct data', function () {
        $member = Member::factory()->create(['wallet_balance' => 0]);
        $selling = Selling::factory()->create(['member_id' => $member->id]);
        $service = new WalletService();

        $topUpTx = $service->topUp($member, 200000, 'Top up from staff');
        expect($topUpTx->type)->toBe('top_up');
        expect($topUpTx->amount)->toBe(200000);
        expect($topUpTx->balance_after)->toBe(200000);

        $payTx = $service->pay($member, 50000, $selling);
        expect($payTx->type)->toBe('payment');
        expect($payTx->amount)->toBe(50000);
        expect($payTx->balance_after)->toBe(150000);
        expect($payTx->sourceable_id)->toBe($selling->id);
    });

    it('member walletTransactions relationship works', function () {
        $member = Member::factory()->create(['wallet_balance' => 0]);
        $service = new WalletService();
        $service->topUp($member, 100000);
        $service->topUp($member, 50000);

        expect($member->walletTransactions()->count())->toBe(2);
    });
});
