<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Support\Facades\Route;

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

// Google OAuth — moved from tenant-web.php because routes outside the
// first middleware group in that file mysteriously return 404 on Vercel.
Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleController::class, 'redirect'])
    ->name('google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'callback'])
    ->name('google.callback');

Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/admin/tenants/export/csv', [\App\Http\Controllers\TenantExportController::class, 'csv'])
        ->name('admin.tenants.export.csv');
    Route::delete('/admin/tenants/{id}/destroy', [\App\Http\Controllers\TenantExportController::class, 'destroy'])
        ->name('admin.tenants.destroy');
});
