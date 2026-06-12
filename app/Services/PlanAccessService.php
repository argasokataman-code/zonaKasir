<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;

class PlanAccessService
{
    protected function centralConnection(): string
    {
        return Config::get('tenancy.database.central_connection', 'testing');
    }

    public function hasFeature(Tenant|string $tenant, string $feature): bool
    {
        $plan = $this->getPlan($tenant);
        if (! $plan) return false;
        return in_array($feature, $plan->features ?? [], true);
    }

    public function getMaxStores(Tenant|string $tenant): int
    {
        $plan = $this->getPlan($tenant);
        return $plan ? (int) ($plan->max_stores ?? 1) : 1;
    }

    public function getMaxUsers(Tenant|string $tenant): int
    {
        $plan = $this->getPlan($tenant);
        return $plan ? (int) ($plan->max_users ?? 1) : 1;
    }

    public function canCreateStore(Tenant|string $tenant, int $currentStoreCount): bool
    {
        return $currentStoreCount < $this->getMaxStores($tenant);
    }

    public function canCreateUser(Tenant|string $tenant, int $currentUserCount): bool
    {
        return $currentUserCount < $this->getMaxUsers($tenant);
    }

    public function getPlan(Tenant|string $tenant): ?Plan
    {
        $subscription = $this->getActiveSubscription($tenant);
        return $subscription?->plan;
    }

    public function getActiveSubscription(Tenant|string $tenant): ?Subscription
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        $conn = $this->centralConnection();

        return Subscription::on($conn)
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['trialing', 'active'])
            ->latest()
            ->first();
    }

    public function isSubscriptionActive(Tenant|string $tenant): bool
    {
        return $this->getActiveSubscription($tenant) !== null;
    }

    public function isOnTrial(Tenant|string $tenant): bool
    {
        return $this->getActiveSubscription($tenant)?->status === 'trialing';
    }

    public function getCurrentPlanFeatures(Tenant|string $tenant): array
    {
        return $this->getPlan($tenant)?->features ?? [];
    }
}