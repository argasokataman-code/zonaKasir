<?php

namespace App\Http\Middleware;

use App\Services\PlanAccessService;
use Closure;
use Illuminate\Http\Request;

class PlanFeatureMiddleware
{
    public function __construct(
        private PlanAccessService $planAccessService
    ) {}

    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            return $next($request);
        }

        if ($this->planAccessService->hasFeature($tenantId, $feature)) {
            return $next($request);
        }

        return response()->json([
            'message' => "Feature '{$feature}' is not available in your current plan",
            'upgrade_url' => '/admin/subscription/upgrade',
        ], 403);
    }
}