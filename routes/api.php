<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::group(['prefix' => 'domain'], function ()
{
    Route::post('/register', RegisteredUserController::class)
        ->name('register')
        ->middleware('throttle:5,1', 'prevent.onprem.register');
});


Route::get('/pricing', [\App\Http\Controllers\Api\PlanController::class, 'index'])
    ->name('pricing');


// Redirect unauthenticated web requests to Filament tenant login
// Used when auth middleware redirects to route('login') via GET
Route::get('/auth/login', function () {
    return redirect('/member/login');
})->name('login.get');

Route::get('/test', function ()
{
    return response()->json([
        'message' => 'Success!',
    ]);
});

// On-prem heartbeat (vendor-side public endpoint, no tenant auth)
Route::post('/v1/onprem/heartbeat', \App\Http\Controllers\Api\OnpremHeartbeatController::class)
    ->name('onprem.heartbeat');

