<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\SubscriptionWebhookController;

Route::group(['prefix' => 'domain'], function ()
{
    Route::post('/register', RegisteredUserController::class)
        ->name('register')
        ->middleware('throttle:5,1');
});

Route::post('/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('webhooks.midtrans');

Route::post('/webhooks/subscription', [SubscriptionWebhookController::class, 'handle'])
    ->name('webhooks.subscription');

Route::post('/webhooks/flip', [App\Http\Controllers\Api\Webhooks\FlipWebhookController::class, 'handle'])
    ->middleware('throttle:100,1')
    ->name('webhooks.flip');

Route::get('/pricing', [\App\Http\Controllers\Api\PlanController::class, 'index'])
    ->name('pricing');

Route::get('/test', function ()
{
    return response()->json([
        'message' => 'Success!',
    ]);
});

