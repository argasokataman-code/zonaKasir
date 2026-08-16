<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RefreshRouteLookups
{
    public function handle(Request $request, Closure $next): mixed
    {
        $routes = app('router')->getRoutes();

        $routes->refreshNameLookups();
        $routes->refreshActionLookups();

        app('url')->setRoutes($routes);

        return $next($request);
    }
}
