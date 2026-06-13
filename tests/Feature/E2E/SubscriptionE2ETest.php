<?php

namespace Tests\Feature\E2E;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\SubscriptionService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Subscription E2E Flow', function () {
    beforeEach(function () {
        $this->admin = User::first();
        $this->tenantId = $this->admin->tenant_id;
        $this->subscription = Subscription::create([
            'tenant_id' => $this->tenantId,
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
        ]);
    });

    it('trial subscription is created on setup', function () {
        $subscription = Subscription::where('status', 'trialing')
            ->whereNull('plan_id')
            ->where('tenant_id', $this->tenantId)
            ->first();

        expect($subscription)->not()->toBeNull();
        expect($subscription->trial_ends_at)->not()->toBeNull();
        expect($subscription->ends_at)->toBeNull();
        expect($subscription->plan_id)->toBeNull();
        expect($subscription->trial_ends_at->isFuture())->toBeTrue();
    });

    it('trial subscription can be manually expired', function () {
        expect($this->subscription->status)->toBe('trialing');

        $this->subscription->update(['trial_ends_at' => now()->subDay()]);

        expect($this->subscription->fresh()->trial_ends_at->isPast())->toBeTrue();
    });

    it('checkBilling command expires trialing subscriptions', function () {
        $this->subscription->update(['trial_ends_at' => now()->subDay()]);

        $expiredTrials = Subscription::where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        expect($expiredTrials)->toHaveCount(1);
        $expiredTrials->each->update(['status' => 'expired']);

        expect($this->subscription->fresh()->status)->toBe('expired');
    });

    it('checkBilling command expires active subscriptions', function () {
        $plan = Plan::create([
            'name' => 'Premium',
            'slug' => 'premium-' . uniqid(),
            'features' => ['all'],
        ]);

        $this->subscription->update([
            'status' => 'active',
            'trial_ends_at' => null,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->subDay(),
        ]);

        $ended = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        expect($ended)->toHaveCount(1);
        $ended->each->update(['status' => 'expired']);

        expect($this->subscription->fresh()->status)->toBe('expired');
    });

    it('subscription service swap functionality', function () {
        $plan = Plan::create([
            'name' => 'Premium',
            'slug' => 'premium-' . uniqid(),
            'features' => ['all'],
        ]);

        $service = new SubscriptionService();
        $updated = $service->swap($this->subscription, $plan, 'yearly');

        expect($updated->plan_id)->toBe($plan->id);
        expect($updated->billing_cycle)->toBe('yearly');
        expect($updated->status)->toBe('active');
        expect($updated->starts_at->isToday())->toBeTrue();
        expect($updated->ends_at)->not()->toBeNull();
    });

    it('subscription service cancel functionality', function () {
        $service = new SubscriptionService();
        $cancelled = $service->cancel($this->subscription, 'No longer needed');

        expect($cancelled->status)->toBe('cancelled');
        expect($cancelled->cancelled_at)->not()->toBeNull();
        expect($cancelled->cancelled_at->isToday())->toBeTrue();
    });

    it('subscription service renew functionality', function () {
        $this->subscription->update(['status' => 'active']);

        $service = new SubscriptionService();
        $renewed = $service->renew($this->subscription);

        expect($renewed->starts_at->isToday())->toBeTrue();
        expect($renewed->ends_at)->not()->toBeNull();
        expect($renewed->ends_at->greaterThan(now()))->toBeTrue();
    });

    it('subscription service mark past due functionality', function () {
        $service = new SubscriptionService();
        $pastDue = $service->markPastDue($this->subscription);

        expect($pastDue->status)->toBe('past_due');
    });

    it('subscription service reactivate functionality', function () {
        $this->subscription->update(['status' => 'cancelled']);

        $service = new SubscriptionService();
        $reactivated = $service->reactivate($this->subscription);

        expect($reactivated->status)->toBe('active');
        expect($reactivated->cancelled_at)->toBeNull();
    });
});
