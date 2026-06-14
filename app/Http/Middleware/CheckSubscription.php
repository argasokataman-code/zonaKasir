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

        // Check if user is on login page — skip subscription check
        if ($request->is('member/login')) {
            return $next($request);
        }

        // For API/JSON requests only — check subscription status
        // Web requests pass through and let expired-layout-kill/expired-overlay handle UI
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return $next($request);
        }

        // Check for expired / past_due subscription
        $blockedSub = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status', ['expired', 'past_due'])
            ->latest()
            ->first();

        if ($blockedSub) {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please upgrade to continue.',
                'redirect' => '/member/subscription',
            ], 403);
        }

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->whereIn('status', ['trialing', 'active'])
            ->latest()
            ->first();

        if (! $subscription) {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please upgrade to continue.',
                'redirect' => '/member/subscription',
            ], 403);
        }

        // Expire trial if past due
        if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);

            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please upgrade to continue.',
                'redirect' => '/member/subscription',
            ], 403);
        }

        // Expire active subscription if past due
        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);

            return response()->json([
                'error' => 'Subscription expired',
                'message' => 'Your subscription has expired. Please upgrade to continue.',
                'redirect' => '/member/subscription',
            ], 403);
        }

        return $next($request);
    }
}
