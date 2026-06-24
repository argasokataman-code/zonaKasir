<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::group(['prefix' => 'domain'], function ()
{
    Route::post('/register', RegisteredUserController::class)
        ->name('register')
        ->middleware('throttle:5,1');
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

