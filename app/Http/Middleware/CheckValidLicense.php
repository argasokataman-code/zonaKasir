<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;

class CheckValidLicense
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            return $next($request);
        }

        $licenseService = app(LicenseService::class);

        // Skip license check for the license-related routes
        if ($request->routeIs('filament.tenant.pages.license*') ||
            $request->routeIs('filament.tenant.auth.*') ||
            $request->routeIs('livewire.update')) {
            return $next($request);
        }

        if (! $licenseService->hasValidLicense($tenantId)) {
            return redirect()->route('filament.tenant.pages.license');
        }

        return $next($request);
    }
}
