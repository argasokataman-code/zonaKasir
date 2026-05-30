<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Settings & Configuration E2E', function () {
    
    describe('Profile Management', function () {
        it('user can get their profile', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/auth/me');
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json('data'))->toHaveKey('id');
            expect($response->json('data'))->toHaveKey('email');
            expect($response->json('data'))->toHaveKey('name');
        });

        it('user can update their profile', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/auth/me', [
                    'name' => 'Updated Name',
                    'email' => $user->email,
                    'phone' => '+62812345678',
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json('data.name'))->toBe('Updated Name');
        });

        it('profile update returns updated data', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/auth/me', [
                    'name' => 'New Name',
                    'email' => $user->email,
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
            expect($response->json())->toHaveKey('data');
            expect($response->json('data'))->toHaveKey('id');
        });

        it('cannot update profile without authentication', function () {
            $response = $this->putJson('/api/auth/me', [
                'name' => 'Updated Name',
            ]);
            
            expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
        });

        it('validates email format on profile update', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/auth/me', [
                    'name' => 'Valid Name',
                    'email' => 'invalid-email',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        it('validates timezone on profile update', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/auth/me', [
                    'name' => 'Valid Name',
                    'email' => $user->email,
                    'timezone' => 'Invalid/Timezone',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        it('validates locale on profile update', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/auth/me', [
                    'name' => 'Valid Name',
                    'email' => $user->email,
                    'locale' => 'invalid_locale',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    });

    describe('About Settings', function () {
        it('user can get about information', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/about');
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('user with permission can update about information', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/about', [
                    'shop_name' => 'My Shop',
                    'address' => 'Jakarta',
                    'phone' => '+62812345678',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });
    });

    describe('Settings Management', function () {
        it('user can get setting by key', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/setting/default_tax');
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });

        it('user with permission can update setting', function () {
            $user = User::first();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/setting', [
                    'key' => 'currency',
                    'value' => 'USD',
                ]);
            
            expect($response->status())->toBe(Response::HTTP_OK);
        });
    });
});
