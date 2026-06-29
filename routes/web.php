<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Support\Facades\Route;

// Handle browser prefetch for livewire/update — only POST is valid
Route::get('/livewire/update', fn () => response()->noContent(405));

Route::get('/', function () {
    return redirect('/landing');
});

Route::get('/landing', function () {
    return response()->file(public_path('landing/index.html'));
});

Route::view('/privacy', 'privacy-policy');
Route::view('/terms', 'terms-of-service');

Route::view('/offline', 'offline');
Route::view('/network-error', 'network-error');

Route::get('/serviceworker.js', function () {
    return response(file_get_contents(public_path('serviceworker.js')), 200, [
        'Content-Type' => 'application/javascript',
    ]);
});

Route::get('/auth/register', RegisterTenantForm::class)
    ->name('auth.register');

Route::get('/auth/google/redirect', function () {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $redirectUri = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/google/callback';

    return \Laravel\Socialite\Facades\Socialite::driver('google')
        ->scopes(['openid', 'profile', 'email'])
        ->with(['prompt' => 'select_account'])
        ->redirectUrl($redirectUri)
        ->stateless()
        ->redirect();
})->name('auth.google.redirect');

Route::get('/auth/google/callback', function () {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $redirectUri = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/google/callback';
    config(['services.google.redirect' => $redirectUri]);

    $controller = app(\App\Http\Controllers\Auth\GoogleController::class);

    return $controller->callback();
})->name('auth.google.callback');

Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/admin/tenants/export/csv', [\App\Http\Controllers\TenantExportController::class, 'csv'])
        ->name('admin.tenants.export.csv');
    Route::delete('/admin/tenants/{id}/destroy', [\App\Http\Controllers\TenantExportController::class, 'destroy'])
        ->name('admin.tenants.destroy');
});
