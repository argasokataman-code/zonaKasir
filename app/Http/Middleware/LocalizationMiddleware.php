<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = auth()->user();
            if ($user) {
                $locale = $user->profile->locale ?? 'en';
                config(['app.locale' => $locale]);
                app()->setLocale($locale);
            }
        } catch (Exception $e) {
            // DB not available — skip locale detection
        }

        return $next($request);
    }
}
