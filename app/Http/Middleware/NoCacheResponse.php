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

        // Prevent browser + SW from caching authenticated pages.
        // Ensures fresh content after logout/account switch.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
