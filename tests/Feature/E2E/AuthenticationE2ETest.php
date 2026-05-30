<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Authentication E2E Flow', function () {
    it('can login via form and access dashboard', function () {
        $user = User::first();
        
        // Login should redirect to dashboard
        $response = $this->post('/member/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        expect($response->status())->toBe(Response::HTTP_FOUND);
    });

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
        
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
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
});
