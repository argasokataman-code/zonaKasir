<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use App\Models\Tenants\Voucher;
use App\Services\VoucherService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member-Specific Vouchers', function () {
    it('global voucher applies to any member', function () {
        $member = Member::factory()->create();
        Voucher::factory()->create([
            'code' => 'GLOBAL10',
            'type' => 'flat',
            'nominal' => 10000,
            'kuota' => 10,
            'start_date' => now()->subDay(),
            'expired' => now()->addDay(),
            'minimal_buying' => 50000,
            'member_id' => null,
        ]);

        $service = new VoucherService();
        $result = $service->applyable('GLOBAL10', 100000, $member->id);

        expect($result)->not->toBeNull();
        expect($result->voucher->code)->toBe('GLOBAL10');
    });

    it('member-specific voucher only applies to assigned member', function () {
        $memberA = Member::factory()->create();
        $memberB = Member::factory()->create();
        Voucher::factory()->create([
            'code' => 'MEMBONUS',
            'type' => 'flat',
            'nominal' => 20000,
            'kuota' => 5,
            'start_date' => now()->subDay(),
            'expired' => now()->addDay(),
            'minimal_buying' => 50000,
            'member_id' => $memberA->id,
        ]);

        $service = new VoucherService();
        $resultA = $service->applyable('MEMBONUS', 100000, $memberA->id);
        $resultB = $service->applyable('MEMBONUS', 100000, $memberB->id);

        expect($resultA)->not->toBeNull();
        expect($resultB)->toBeNull();
    });

    it('global voucher also works without member context', function () {
        Voucher::factory()->create([
            'code' => 'ALLFREE',
            'type' => 'flat',
            'nominal' => 5000,
            'kuota' => 10,
            'start_date' => now()->subDay(),
            'expired' => now()->addDay(),
            'minimal_buying' => 20000,
            'member_id' => null,
        ]);

        $service = new VoucherService();
        $result = $service->applyable('ALLFREE', 50000);

        expect($result)->not->toBeNull();
    });

    it('member vouchers relationship returns assigned vouchers', function () {
        $member = Member::factory()->create();
        Voucher::factory()->create(['code' => 'VOUCH1', 'member_id' => $member->id]);
        Voucher::factory()->create(['code' => 'VOUCH2', 'member_id' => $member->id]);
        Voucher::factory()->create(['code' => 'GLOBAL', 'member_id' => null]);

        expect($member->vouchers()->count())->toBe(2);
    });

    it('voucher scopes filter correctly', function () {
        $member = Member::factory()->create();
        Voucher::factory()->create(['code' => 'GLOBAL1', 'member_id' => null]);
        Voucher::factory()->create(['code' => 'PERSONAL1', 'member_id' => $member->id]);

        expect(Voucher::global()->count())->toBe(1);
        expect(Voucher::forMember($member->id)->count())->toBe(1);
    });
});
