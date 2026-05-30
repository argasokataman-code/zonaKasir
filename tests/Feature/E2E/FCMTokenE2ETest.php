<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('FCM Token Management E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can register FCM token', function () {
        $response = $this->withToken($this->token)
            ->postJson('/api/register-fcm-token', [
                'fcm_token' => 'dummy_fcm_token_' . uniqid(),
            ]);

        expect($response->status())->toBeIn([Response::HTTP_OK, Response::HTTP_CREATED]);
    });

    it('validates fcm token is provided', function () {
        $response = $this->withToken($this->token)
            ->postJson('/api/register-fcm-token', []);

        expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('requires authentication to register token', function () {
        $response = $this->postJson('/api/register-fcm-token', [
            'fcm_token' => 'test_token',
        ]);

        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });

    it('can update existing fcm token', function () {
        $token1 = 'fcm_token_' . uniqid();
        $token2 = 'fcm_token_' . uniqid();

        $this->withToken($this->token)
            ->postJson('/api/register-fcm-token', ['fcm_token' => $token1]);

        $response = $this->withToken($this->token)
            ->postJson('/api/register-fcm-token', ['fcm_token' => $token2]);

        expect($response->status())->toBeIn([Response::HTTP_OK, Response::HTTP_CREATED]);
    });
});
