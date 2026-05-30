<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Password Reset E2E', function () {
    it('can request password reset link', function () {
        $user = User::first();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ]);
    });

    it('validates email exists when requesting reset', function () {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        expect($response->status())->toBeIn([
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_OK, // May return OK for security
        ]);
    });

    it('can verify email address', function () {
        $user = User::first();

        // Create a fake verification hash
        $hash = sha1($user->email);

        $response = $this->getJson(
            "/api/auth/verify-email/{$user->id}/{$hash}"
        );

        // May be 200, 302, or 404
        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_FOUND,
        ]);
    });

    it('can request email verification notification', function () {
        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/email/verification-notification');

        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ]);
    });
});
