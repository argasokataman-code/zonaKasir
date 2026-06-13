<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\LoyaltyPointLog;
use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\User;
use App\Services\Tenants\LoyaltyPointService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Loyalty Points System', function () {
    beforeEach(function () {
        $this->user = User::first();
    });

    it('earnPoints creates a loyalty point log for a paid selling with member', function () {
        $member = Member::factory()->create(['total_points' => 0]);
        $selling = Selling::factory()
            ->for($member)
            ->create([
                'total_price' => 100000,
                'discount_price' => 0,
                'tax_price' => 0,
                'total_discount_per_item' => 0,
                'is_paid' => true,
                'payed_money' => 100000,
            ]);

        $selling->refresh();

        $service = new LoyaltyPointService();
        $service->earnPoints($selling);

        $member->refresh();

        expect($member->total_points)->toBeGreaterThan(0);
        expect(LoyaltyPointLog::where('member_id', $member->id)->count())->toBe(1);
    });

    it('earnPoints skips when selling has no member', function () {
        $member = Member::factory()->create(['total_points' => 0]);
        $selling = Selling::factory()
            ->create([
                'member_id' => null,
                'total_price' => 100000,
                'discount_price' => 0,
                'tax_price' => 0,
                'total_discount_per_item' => 0,
            ]);

        $service = new LoyaltyPointService();
        $service->earnPoints($selling);

        expect($member->fresh()->total_points)->toBe(0);
    });

    it('redeemPoints deducts points and returns discount amount', function () {
        $member = Member::factory()->create(['total_points' => 500]);
        $service = new LoyaltyPointService();

        $amount = $service->redeemPoints($member, 200);
        $member->refresh();

        expect($member->total_points)->toBe(300);
        expect($amount)->toBe(200);
    });

    it('redeemPoints throws when insufficient points', function () {
        $member = Member::factory()->create(['total_points' => 50]);
        $service = new LoyaltyPointService();

        $service->redeemPoints($member, 100);
    })->throws(\InvalidArgumentException::class);

    it('getPointsForAmount calculates correctly based on earn rate', function () {
        $service = new LoyaltyPointService();

        expect($service->getPointsForAmount(10000))->toBe(1000);
        expect($service->getPointsForAmount(5))->toBe(0); // Below earn rate threshold
    });

    it('getAmountForPoints converts points to currency', function () {
        $service = new LoyaltyPointService();

        expect($service->getAmountForPoints(100))->toBe(100);
        expect($service->getAmountForPoints(0))->toBe(0);
    });

    it('member availablePoints accessor returns total_points', function () {
        $member = Member::factory()->create(['total_points' => 350]);

        expect($member->available_points)->toBe(350);
    });
});
