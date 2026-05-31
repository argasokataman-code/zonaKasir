<?php

namespace App\Http\Middleware;

use Barryvdh\Debugbar\Facades\Debugbar;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableDebugbar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Disable Debugbar globally when app debug is false or when explicitly disabled
        try {
            // Disable Debugbar in local environment or when app debug is false
            if (app()->environment('local') || ! config('app.debug')) {
                Debugbar::disable();
            }
        } catch (\Throwable $e) {
            // ignore if Debugbar not available
        }

        return $next($request);
    }
}
