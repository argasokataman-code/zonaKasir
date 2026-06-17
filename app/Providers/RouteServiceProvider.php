<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->mapWebRoutes();
            $this->mapApiRoutes();
            $this->mapTenantRoutes();
            $this->mapStorageRoutes();
        });
    }

    protected function mapWebRoutes()
    {
        foreach ($this->centralDomains() as $domain) {
            Route::middleware('web')
                ->domain($domain)
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        }
    }

    protected function mapApiRoutes()
    {
        foreach ($this->centralDomains() as $domain) {
            Route::prefix('api')
                ->domain($domain)
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));
        }
    }

    protected function mapStorageRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/storage.php'));
    }

    protected function mapTenantRoutes()
    {
        // Web routes: `web` middleware ONLY (session, CSRF, cookies).
        Route::group([], base_path('routes/tenant-web.php'));

        // API routes: `api` middleware ONLY (Sanctum, throttle, bindings).
        Route::group([], base_path('routes/tenant-api.php'));
    }

    protected function centralDomains(): array
    {
        return [parse_url(config('app.url'), PHP_URL_HOST)];
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
