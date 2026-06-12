<?php

namespace Tests\Feature\E2E;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Subscription E2E Flow', function () {
    beforeEach(function () {
        $this->admin = User::first();
        $this->token = $this->admin->createToken('test')->plainTextToken;
    });

    it('trial subscription is created on tenant registration', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::where('status', 'trialing')
                ->whereNull('plan_id')
                ->first();
        });

        expect($subscription)->not()->toBeNull();
        expect($subscription->trial_ends_at)->not()->toBeNull();
        expect($subscription->ends_at)->toBeNull();
        expect($subscription->plan_id)->toBeNull();
        expect($subscription->trial_ends_at->isFuture())->toBeTrue();
        expect($subscription->trial_ends_at->diffInDays(now(), true))
            ->toBeGreaterThanOrEqual(13);
    });

    it('trial subscription can be manually expired', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::where('status', 'trialing')->first();
        });
        expect($subscription->status)->toBe('trialing');

        $subscription->update(['trial_ends_at' => now()->subDay()]);
        $subscription->refresh();

        expect($subscription->trial_ends_at->isPast())->toBeTrue();
    });

    it('checkBilling command expires trialing subscriptions', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::where('status', 'trialing')->first();
        });
        $subscription->update(['trial_ends_at' => now()->subDay()]);

        $expiredTrials = Tenancy::central(function () {
            return Subscription::where('status', 'trialing')
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<', now())
                ->get();
        });

        expect($expiredTrials)->toHaveCount(1);
        $expiredTrials->each->update(['status' => 'expired']);

        $subscription->refresh();
        expect($subscription->status)->toBe('expired');
    });

    it('checkBilling command expires active subscriptions', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::where('status', 'trialing')->first();
        });
        $subscription->update(['status' => 'active', 'trial_ends_at' => null]);

        $plan = Tenancy::central(function () {
            return Plan::create([
                'name' => 'Premium',
                'slug' => 'premium-' . uniqid(),
                'features' => ['all'],
            ]);
        });

        $subscription->update([
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->subDay(),
        ]);

        $ended = Tenancy::central(function () {
            return Subscription::where('status', 'active')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now())
                ->get();
        });

        expect($ended)->toHaveCount(1);
        $ended->each->update(['status' => 'expired']);

        $subscription->refresh();
        expect($subscription->status)->toBe('expired');
    });

    it('subscription service swap functionality', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::first();
        });
        $plan = Tenancy::central(function () {
            return Plan::create([
                'name' => 'Premium',
                'slug' => 'premium-' . uniqid(),
                'features' => ['all'],
            ]);
        });

        $service = new SubscriptionService();
        $updated = $service->swap($subscription, $plan, 'yearly');

        expect($updated->plan_id)->toBe($plan->id);
        expect($updated->billing_cycle)->toBe('yearly');
        expect($updated->status)->toBe('active');
        expect($updated->starts_at->isToday())->toBeTrue();
        expect($updated->ends_at)->not()->toBeNull();
    });

    it('subscription service cancel functionality', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::first();
        });

        $service = new SubscriptionService();
        $cancelled = $service->cancel($subscription, 'No longer needed');

        expect($cancelled->status)->toBe('cancelled');
        expect($cancelled->cancelled_at)->not()->toBeNull();
        expect($cancelled->cancelled_at->isToday())->toBeTrue();
    });

    it('subscription service renew functionality', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::first();
        });
        $subscription->update(['status' => 'active']);

        $service = new SubscriptionService();
        $renewed = $service->renew($subscription);
        $renewed->refresh();

        expect($renewed->starts_at->isToday())->toBeTrue();
        expect($renewed->ends_at)->not()->toBeNull();
        expect($renewed->ends_at->greaterThan(now()))->toBeTrue();
    });

    it('subscription service mark past due functionality', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::first();
        });

        $service = new SubscriptionService();
        $pastDue = $service->markPastDue($subscription);

        expect($pastDue->status)->toBe('past_due');
    });

    it('subscription service reactivate functionality', function () {
        $subscription = Tenancy::central(function () {
            return Subscription::first();
        });
        $subscription->update(['status' => 'cancelled']);
        $subscription->refresh();

        $service = new SubscriptionService();
        $reactivated = $service->reactivate($subscription);

        expect($reactivated->status)->toBe('active');
        expect($reactivated->cancelled_at)->toBeNull();
    });
});