<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Route 'login' resolves to /api/auth/login (API endpoint).
            // Redirect to the appropriate Filament panel login instead.
            if (str_starts_with($request->path(), 'admin')) {
                return '/admin/login';
            }

            return '/member/login';
        }
    }
}
