<?php

namespace Tests\Feature\E2E;

use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\CouponService;
use Illuminate\Http\Response;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Coupon E2E Flow', function () {
    beforeEach(function () {
        $this->admin = User::first();
    });

    // --- Model / isValid ---

    it('coupon is valid by default', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->create();
        });

        expect($coupon->isValid())->toBeTrue();
    });

    it('isValid returns false when max_redemptions reached', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->exhausted()->create();
        });

        expect($coupon->isValid())->toBeFalse();
    });

    it('isValid returns false when expired', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->expired()->create();
        });

        expect($coupon->isValid())->toBeFalse();
    });

    // --- CouponService redeem ---

    it('redeem percentage coupon increments used_count', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->percentage()->create();
        });
        $tenantId = tenant('id');

        $service = new CouponService();
        $result = $service->redeem($coupon->code, $tenantId);

        expect($result['success'])->toBeTrue();
        expect($result['type'])->toBe('percentage');
        $coupon->refresh();
        expect($coupon->used_count)->toBe(1);
    });

    it('redeem nominal coupon increments used_count', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->nominal()->create();
        });
        $tenantId = tenant('id');

        $service = new CouponService();
        $result = $service->redeem($coupon->code, $tenantId);

        expect($result['success'])->toBeTrue();
        expect($result['type'])->toBe('nominal');
        $coupon->refresh();
        expect($coupon->used_count)->toBe(1);
    });

    it('redeem trial_extension coupon extends trial_ends_at', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->trialExtension()->create(['trial_days' => 7]);
        });
        $tenantId = tenant('id');

        $subscription = Tenancy::central(function () use ($tenantId) {
            return Subscription::where('tenant_id', $tenantId)->first();
        });
        $originalTrialEnd = $subscription->trial_ends_at->copy();

        $service = new CouponService();
        $result = $service->redeem($coupon->code, $tenantId);

        expect($result['success'])->toBeTrue();
        expect($result['type'])->toBe('trial_extension');
        expect($result['value'])->toBe(7);

        $subscription->refresh();
        expect((int) $subscription->trial_ends_at->diffInDays($originalTrialEnd, true))->toBe(7);

        $coupon->refresh();
        expect($coupon->used_count)->toBe(1);
    });

    it('redeem fails for invalid coupon code', function () {
        $service = new CouponService();

        expect(fn () => $service->redeem('INVALID_CODE', tenant('id')))
            ->toThrow(\Exception::class, 'tidak ditemukan');
    });

    it('redeem fails for exhausted coupon', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->exhausted()->create();
        });

        $service = new CouponService();

        expect(fn () => $service->redeem($coupon->code, tenant('id')))
            ->toThrow(\Exception::class, 'tidak valid');
    });

    it('redeem fails for expired coupon', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->expired()->create();
        });

        $service = new CouponService();

        expect(fn () => $service->redeem($coupon->code, tenant('id')))
            ->toThrow(\Exception::class, 'tidak valid');
    });

    // --- API endpoint ---

    it('POST /api/coupon/redeem returns success for valid coupon', function () {
        $coupon = Tenancy::central(function () {
            return Coupon::factory()->percentage()->create();
        });

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/coupon/redeem', ['code' => $coupon->code]);

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json('data.success'))->toBeTrue();
    });

    it('POST /api/coupon/redeem returns 400 for invalid code', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/coupon/redeem', ['code' => 'WRONG']);

        expect($response->status())->toBe(Response::HTTP_BAD_REQUEST);
        expect($response->json('message'))->toContain('tidak ditemukan');
    });

    it('POST /api/coupon/redeem validates code is required', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/coupon/redeem', []);

        // 422 when validation fails
        expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('POST /api/coupon/redeem requires authentication', function () {
        $response = $this->postJson('/api/coupon/redeem', ['code' => 'ANY']);

        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});