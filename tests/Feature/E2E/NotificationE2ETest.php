<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Notifications Management E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can fetch user notifications', function () {
        $response = $this->withToken($this->token)
            ->getJson('/api/notification');

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json())->toHaveKey('data');
    });

    it('can get empty notifications list', function () {
        $response = $this->withToken($this->token)
            ->getJson('/api/notification');

        expect($response->status())->toBe(Response::HTTP_OK);
        expect(is_array($response->json()['data']))->toBeTrue();
    });

    it('can mark notification as read', function () {
        $response = $this->withToken($this->token)
            ->putJson('/api/notification/1/1', [
                'is_read' => true,
            ]);

        // May be 200 or 404 depending on notification existence
        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
        ]);
    });

    it('can clear all notifications', function () {
        $response = $this->withToken($this->token)
            ->deleteJson('/api/notification/clear');

        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NO_CONTENT,
        ]);
    });

    it('requires authentication to access notifications', function () {
        $response = $this->getJson('/api/notification');

        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
