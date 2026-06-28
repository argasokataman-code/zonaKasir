<?php

namespace App\Http\Middleware;

use App\Models\Tenants\About;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DynamicPwaManifest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== 'manifest.json') {
            return $next($request);
        }

        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return $next($request);
        }

        if (! Schema::hasTable('abouts')) {
            return $next($request);
        }

        $about = About::select('primary_color')->first();

        if ($about && $about->primary_color) {
            $color = $about->primary_color;
            config([
                'laravelpwa.manifest.theme_color' => $color,
                'laravelpwa.manifest.background_color' => $color,
                'laravelpwa.manifest.status_bar' => $color,
            ]);
        }

        return $next($request);
    }
}
