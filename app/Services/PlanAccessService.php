<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;

class PlanAccessService
{
    public function hasFeature(string $tenant, string $feature): bool
    {
        $plan = $this->getPlan($tenant);
        if (! $plan) return false;
        return in_array($feature, $plan->features ?? [], true);
    }

    public function getMaxStores(string $tenant): int
    {
        $plan = $this->getPlan($tenant);
        return $plan ? (int) ($plan->max_stores ?? 1) : 1;
    }

    public function getMaxUsers(string $tenant): int
    {
        $plan = $this->getPlan($tenant);
        return $plan ? (int) ($plan->max_users ?? 1) : 1;
    }

    public function canCreateStore(string $tenant, int $currentStoreCount): bool
    {
        return $currentStoreCount < $this->getMaxStores($tenant);
    }

    public function canCreateUser(string $tenant, int $currentUserCount): bool
    {
        return $currentUserCount < $this->getMaxUsers($tenant);
    }

    public function getPlan(string $tenant): ?Plan
    {
        $subscription = $this->getActiveSubscription($tenant);
        return $subscription?->plan;
    }

    public function getActiveSubscription(string $tenant): ?Subscription
    {
        return Subscription::with('plan')
            ->where('tenant_id', $tenant)
            ->whereIn('status', ['trialing', 'active'])
            ->latest()
            ->first();
    }

    public function isSubscriptionActive(string $tenant): bool
    {
        return $this->getActiveSubscription($tenant) !== null;
    }

    public function isOnTrial(string $tenant): bool
    {
        return $this->getActiveSubscription($tenant)?->status === 'trialing';
    }

    public function getCurrentPlanFeatures(string $tenant): array
    {
        return $this->getPlan($tenant)?->features ?? [];
    }
}
