<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
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
            return $this->blockIfApi($request);
        }

        // Expire trial if past due
        if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);

            return $this->blockIfApi($request);
        }

        // Expire active subscription if past due
        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);

            return $this->blockIfApi($request);
        }

        return $next($request);
    }

    private function blockIfApi(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please upgrade to continue.',
                'redirect' => '/member/subscription',
            ], 403);
        }

        // For web requests, let the overlay handle it
        return redirect('/member');
    }
}
