<?php

namespace App\Http\Middleware;

use App\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $adminDomains = array_filter((array) config('tenancy.admin_domains'));
        $centralDomains = array_filter((array) config('tenancy.central_domains'));

        // Skip for admin and central domains
        if (in_array($host, $adminDomains, true) || in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        try {
            $resolver = app(DomainTenantResolver::class);
            $tenant = $resolver->resolve($host);

            if ($tenant && ! $tenant->is_active) {
                return response()->view('errors.tenant-suspended', [
                    'reason' => $tenant->suspension_reason ?? __('Your account has been suspended. Please contact support.'),
                ], 403);
            }
        } catch (\Throwable $e) {
            // If tenant can't be resolved, proceed normally
        }

        return $next($request);
    }
}
