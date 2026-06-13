<?php

namespace Tests\Feature\E2E;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\PlanAccessService;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Feature Access E2E Flow', function () {
    beforeEach(function () {
        $this->admin = User::first();
        $this->tid = $this->admin->tenant_id;
        Subscription::where('tenant_id', $this->tid)->delete();
    });

    it('GET /api/billing/features returns plan info', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic-' . uniqid(),
            'price_monthly' => 50000,
            'max_stores' => 5,
            'max_users' => 10,
            'features' => ['pos', 'report'],
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        $p = $access->getPlan($tid);
        expect($p)->not()->toBeNull();
        expect((int) $p->max_stores)->toBe(5);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/billing/features');

        expect($response->status())->toBe(Response::HTTP_OK);

        $data = $response->json('data');
        expect($data)->toHaveKey('plan');
        expect($data)->toHaveKey('limits');
        expect($data['limits'])->toHaveKeys(['max_stores', 'max_users']);
    });

    it('PlanAccessService.getPlan returns plan', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-' . uniqid(),
            'price_monthly' => 100000,
            'max_stores' => 7,
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        $foundPlan = $access->getPlan($tid);

        expect($foundPlan)->not()->toBeNull();
        expect((int) $foundPlan->max_stores)->toBe(7);
    });

    it('PlanAccessService.hasFeature works', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Standard',
            'slug' => 'standard-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['pos', 'report'],
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->hasFeature($tid, 'pos'))->toBeTrue();
        expect($access->hasFeature($tid, 'report'))->toBeTrue();
        expect($access->hasFeature($tid, 'multi_store'))->toBeFalse();
    });

    it('PlanAccessService.getMaxStores returns plan limit', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'ent-' . uniqid(),
            'price_monthly' => 500000,
            'max_stores' => 99,
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->getMaxStores($tid))->toBe(99);
    });

    it('PlanAccessService.canCreateStore respects limits', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Small',
            'slug' => 'small-' . uniqid(),
            'price_monthly' => 25000,
            'max_stores' => 2,
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->canCreateStore($tid, 0))->toBeTrue();
        expect($access->canCreateStore($tid, 1))->toBeTrue();
        expect($access->canCreateStore($tid, 2))->toBeFalse();
    });

    it('PlanAccessService.canCreateUser respects limits', function () {
        $tid = $this->tid;

        $plan = Plan::create([
            'name' => 'Team',
            'slug' => 'team-' . uniqid(),
            'price_monthly' => 50000,
            'max_users' => 3,
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->canCreateUser($tid, 0))->toBeTrue();
        expect($access->canCreateUser($tid, 2))->toBeTrue();
        expect($access->canCreateUser($tid, 3))->toBeFalse();
    });

    it('hasFeature returns false when no active subscription', function () {
        $access = new PlanAccessService();
        expect($access->hasFeature('nonexistent-tenant', 'pos'))->toBeFalse();
    });

    it('isOnTrial works correctly', function () {
        $tid = $this->tid;

        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $access = new PlanAccessService();
        expect($access->isOnTrial($tid))->toBeTrue();
    });

    it('expired subscription shows is_subscription_active false', function () {
        $tid = $this->tid;

        Subscription::where('tenant_id', $tid)->delete();

        $access = new PlanAccessService();
        expect($access->isSubscriptionActive($tid))->toBeFalse();
    });
});
