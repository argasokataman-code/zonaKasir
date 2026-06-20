<?php

use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\SubscriptionWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/api/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('webhooks.midtrans')
    ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class]);

Route::post('/api/webhooks/subscription', [SubscriptionWebhookController::class, 'handle'])
    ->name('webhooks.subscription');

Route::post('/api/webhooks/flip', [App\Http\Controllers\Api\Webhooks\FlipWebhookController::class, 'handle'])
    ->middleware('throttle:100,1')
    ->name('webhooks.flip');
