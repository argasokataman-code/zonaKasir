<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'pages/welcome');

Route::view('/offline', 'offline');

Route::get('/serviceworker.js', function () {
    return response()->file(public_path('serviceworker.js'))
        ->header('Content-Type', 'application/javascript');
});

Route::get('/auth/register', RegisterTenantForm::class)
    ->name('auth.register');

Route::middleware([
    'web',
])
    ->prefix('admin')
    ->group(function () {
        //
    });

Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/admin/tenants/export/csv', [\App\Http\Controllers\TenantExportController::class, 'csv'])
        ->name('admin.tenants.export.csv');
    Route::delete('/admin/tenants/{id}/destroy', [\App\Http\Controllers\TenantExportController::class, 'destroy'])
        ->name('admin.tenants.destroy');
});

// Midtrans webhook - no auth, no throttle
Route::post('/webhooks/midtrans', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle'])
    ->name('webhooks.midtrans');

// Serve uploaded files from storage/app/public
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (! file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');
