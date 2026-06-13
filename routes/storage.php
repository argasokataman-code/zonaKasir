<?php

use Illuminate\Support\Facades\Route;

Route::withoutMiddleware([
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\InitializeTenancyByDomain::class,
    \App\Http\Middleware\CheckTenantActive::class,
    \App\Http\Middleware\CheckSubscription::class,
    \App\Http\Middleware\DisableDebugbar::class,
    \App\Http\Middleware\LocalizationMiddleware::class,
    \App\Http\Middleware\LogSqlQueries::class,
])->get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (! file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');
