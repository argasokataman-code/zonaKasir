<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function swap(Subscription $subscription, Plan $plan, string $billingCycle = 'monthly'): Subscription
    {
        DB::beginTransaction();
        try {
            $now = now();
            $subscription->update([
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'starts_at' => $now,
                'ends_at' => $this->calculateEndDate($billingCycle),
                'status' => 'active',
                'cancelled_at' => null,
            ]);
            DB::commit();
            return $subscription->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Subscription swap failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        return $subscription->fresh();
    }

    public function renew(Subscription $subscription): Subscription
    {
        $subscription->update([
            'starts_at' => now(),
            'ends_at' => $this->calculateEndDate($subscription->billing_cycle),
        ]);
        return $subscription->fresh();
    }

    public function markPastDue(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'past_due']);
        return $subscription->fresh();
    }

    public function reactivate(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);
        return $subscription->fresh();
    }

    private function calculateEndDate(string $billingCycle, ?\DateTimeInterface $from = null): ?string
    {
        $from = $from ?? now();
        return match ($billingCycle) {
            'monthly' => $from->copy()->addMonth(),
            'yearly' => $from->copy()->addYear(),
            default => null,
        };
    }
}
