<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenants\Reports\PurchasingReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CashierReportController;
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\SellingReportController;
use App\Livewire\ResetPassword;
use Illuminate\Support\Facades\Route;

// ─── Web routes for tenant panel ───────────────────────────
// Loaded with `web` middleware ONLY (no api middleware stacking).

Route::middleware([
    'web',
    \App\Http\Middleware\CheckSubscription::class,
])
->group(function () {
    Route::get('/', function () {
        return redirect()->to('/member');
    });
    Route::get('/member/purchasing-report/generate', PurchasingReportController::class)
        ->name('purchasing-report.generate');
    Route::get('/member/selling-report/generate', SellingReportController::class)
        ->name('selling-report.generate');
    Route::get('/member/product-report/generate', ProductReportController::class)
        ->name('product-report.generate');
    Route::get('/member/cashier-report/generate', CashierReportController::class)
        ->name('cashier-report.generate');
    Route::view('/member/sellings/{selling}/print', 'filament.tenant.pages.selling.print-receipt')
        ->name('selling.print');
    Route::get('/reset-password/{token}', ResetPassword::class)
        ->middleware('guest')
        ->name('reset-password.index');
    // Support traditional form POST to the Filament tenant login page so non-JS
    // form submissions don't hit a 405. API logins should continue to use
    // `POST /api/auth/login`.
    Route::post('/member/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('filament.tenant.auth.login.post');

    // Web logout — revokes tokens, invalidates session, clears cookie.
    // Registered here so the custom controller handles cleanup properly,
    // overriding Filament's internal LogoutController which skips token revocation.
    Route::post('/member/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('filament.tenant.auth.logout');

    // Welcome modal dismiss (marks welcomed_at)
    Route::post('/welcome/dismiss', function () {
        $user = auth()->user();
        if ($user && ! $user->welcomed_at) {
            $user->update(['welcomed_at' => now()]);
        }
        session()->forget(['welcome_type', 'welcome_data']);
        return response()->json(['status' => 'ok']);
    })->middleware('auth')->name('welcome.dismiss');
});
Route::get('/api/tenant-test', function() { return response()->json(['ok' => true]); });
Route::get('/public-api/pricing', [\App\Http\Controllers\Api\PlanController::class, 'index']);
