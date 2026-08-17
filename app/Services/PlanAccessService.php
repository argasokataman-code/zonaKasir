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
        return Subscription::select('id', 'tenant_id', 'plan_id', 'status')
            ->with('plan:id,name,slug,max_stores,max_users,features')
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

    /**
     * FR-9.6: Check if tenant can access a cafe feature.
     * Lite = open_bill + table + struk only.
     * Pro = all cafe features (split_bill, kds, shift_xz).
     */
    public function canAccessCafeFeature(string $tenant, string $feature): bool
    {
        $features = $this->getCurrentPlanFeatures($tenant);

        // Pro features require explicit feature key
        $proFeatures = ['cafe_split_bill', 'cafe_kds', 'cafe_shift_xz'];

        if (in_array($feature, $proFeatures, true)) {
            return in_array($feature, $features, true);
        }

        // Lite features (open_bill, table, struk) = available to all cafe plans
        return true;
    }
}
