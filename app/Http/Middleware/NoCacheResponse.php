<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCacheResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow short browser cache (bfcache works, back/forward instant).
        // must-revalidate ensures fresh content after max-age.
        $response->headers->set('Cache-Control', 'private, max-age=60, must-revalidate');
        $response->headers->set('Pragma', 'cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
