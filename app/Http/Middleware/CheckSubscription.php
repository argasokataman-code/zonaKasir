<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $adminDomains = array_filter((array) config('tenancy.admin_domains'));
        $centralDomains = array_filter((array) config('tenancy.central_domains'));

        if (in_array($host, $adminDomains, true) || in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        $tenantId = tenant('id');
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
