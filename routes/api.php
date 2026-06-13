<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Api\MidtransWebhookController;

Route::group(['prefix' => 'domain'], function ()
{
    Route::post('/register', RegisteredUserController::class)
        ->name('register')
        ->middleware('throttle:5,1');
});

Route::post('/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('webhooks.midtrans');

Route::post('/webhooks/flip', [App\Http\Controllers\Api\Webhooks\FlipWebhookController::class, 'handle'])
    ->name('webhooks.flip');

Route::get('/test', function ()
{
    return response()->json([
        'message' => 'Success!',
    ]);
});

