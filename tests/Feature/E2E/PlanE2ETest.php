<?php

namespace Tests\Feature\E2E;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature/E2E/PlanE2ETest.php');

describe('Plan E2E Flow', function () {
    beforeEach(function () {
        $this->slug = fn () => 'plan-' . uniqid();
    });

    it('plan can be created', function () {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => value($this->slug),
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_stores' => 1,
            'max_users' => 5,
            'is_active' => true,
            'features' => ['pos', 'report'],
        ]);

        expect($plan)->toBeInstanceOf(Plan::class);
        expect($plan->name)->toBe('Starter');
        expect($plan->price_monthly)->toBe(99000);
    });

    it('plan can be created via factory', function () {
        $plan = Plan::factory()->create();

        expect($plan->name)->not()->toBeEmpty();
        expect($plan->slug)->not()->toBeEmpty();
        expect((int) $plan->price_monthly)->toBeGreaterThan(0);
        expect($plan->is_active)->toBeTrue();
    });

    it('plan can be updated', function () {
        $plan = Plan::factory()->create();

        $plan->update([
            'name' => 'Pro Updated',
            'price_monthly' => 199000,
        ]);

        expect($plan->fresh()->name)->toBe('Pro Updated');
        expect((string) $plan->fresh()->price_monthly)->toBe('199000.00');
    });

    it('plan can be deleted', function () {
        $plan = Plan::factory()->create();
        $planId = $plan->id;

        $plan->delete();

        expect(Plan::find($planId))->toBeNull();
    });

    it('plan slug is unique', function () {
        $slug = value($this->slug);
        Plan::create([
            'name' => 'Plan A',
            'slug' => $slug,
            'price_monthly' => 50000,
        ]);

        expect(fn () => Plan::create([
            'name' => 'Plan B',
            'slug' => $slug,
            'price_monthly' => 100000,
        ]))->toThrow(UniqueConstraintViolationException::class);
    });

    it('free plan factory creates valid free plan', function () {
        $plan = Plan::factory()->free()->create(['slug' => 'free-' . uniqid()]);

        expect($plan->price_monthly)->toBe(0);
        expect($plan->price_yearly)->toBeNull();
        expect((int) $plan->max_stores)->toBe(1);
        expect($plan->features)->toContain('pos');
    });

    it('enterprise plan factory creates enterprise plan', function () {
        $plan = Plan::factory()->enterprise()->create(['slug' => 'enterprise-' . uniqid()]);

        expect($plan->price_monthly)->toBe(500000);
        expect((int) $plan->max_stores)->toBe(99);
        expect($plan->features)->toContain('multi_store');
    });

    it('inactive plan factory creates inactive plan', function () {
        $plan = Plan::factory()->inactive()->create();

        expect($plan->is_active)->toBeFalse();
    });

    it('plan has many subscriptions', function () {
        $plan = Plan::factory()->create();
        $tenantId = 'rel-test-' . uniqid();

        Subscription::factory()
            ->count(3)
            ->active()
            ->create([
                'plan_id' => $plan->id,
                'tenant_id' => $tenantId,
            ]);

        expect($plan->subscriptions)->toHaveCount(3);
        expect($plan->subscriptions->first())->toBeInstanceOf(Subscription::class);
    });

    it('subscription can use plan in swap', function () {
        $plan = Plan::factory()->create();
        $subscription = Subscription::create([
            'tenant_id' => 'swap-test-' . uniqid(),
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $service = new SubscriptionService();
        $result = $service->swap($subscription, $plan, 'yearly');

        expect($result->plan_id)->toBe($plan->id);
        expect($result->billing_cycle)->toBe('yearly');
    });

    it('plan factory default values are correct', function () {
        $plan = Plan::factory()->create(['slug' => 'defaults-' . uniqid()]);

        expect((int) $plan->max_stores)->toBeGreaterThanOrEqual(1);
        expect((int) $plan->max_users)->toBeGreaterThanOrEqual(1);
        expect($plan->is_active)->toBeTrue();
    });
});