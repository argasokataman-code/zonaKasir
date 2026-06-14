<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

class TenantIsolationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Set tenant context from authenticated user
        $user = $request->user();
        if ($user && $user->tenant_id) {
            TenantContext::set($user->tenant_id);
            session(['tenant_id' => $user->tenant_id]);
        }

        $response = $next($request);

        // Clear context after response
        TenantContext::reset();

        return $response;
    }
}
