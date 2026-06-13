<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            return $next($request);
        }

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status', ['trialing', 'active'])
            ->latest()
            ->first();

        if (! $subscription) {
            return $next($request);
        }

        if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);
        }

        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);
        }

        return $next($request);
    }
}
