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

// Google OAuth discovery (required by Google Identity Services library)
Route::get('/auth/login', function () {
    return response()->json([], 200);
});

Route::get('/test', function ()
{
    return response()->json([
        'message' => 'Success!',
    ]);
});

