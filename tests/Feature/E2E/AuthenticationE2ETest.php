<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Authentication E2E Flow', function () {
    it('can login via API with credentials', function () {
        $user = User::first();
        
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json())->toHaveKey('token');
        expect($response->json())->toHaveKey('user');
    });

    it('can logout successfully', function () {
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout');
        
        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('cannot login with invalid credentials', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);
        
        // Should fail validation (422) or unauthorized (401)
        expect($response->status())->toBeIn([Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_UNAUTHORIZED]);
    });

    it('user can access authenticated routes when logged in', function () {
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');
        
        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('user cannot access authenticated routes without token', function () {
        $response = $this->getJson('/api/auth/me');
        
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });

    it('can access profile after successful login', function () {
        $user = User::first();
        
        // Login and get token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        expect($loginResponse->status())->toBe(Response::HTTP_OK);
        
        $token = $loginResponse->json()['token'];
        
        // Use token to access protected endpoint
        $profileResponse = $this->withToken($token)
            ->getJson('/api/auth/me');
        
        expect($profileResponse->status())->toBe(Response::HTTP_OK);
        expect($profileResponse->json()['data'])->toHaveKey('email');
        expect($profileResponse->json()['data']['email'])->toBe($user->email);
    });
});
