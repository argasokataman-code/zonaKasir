<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Authorization & Permissions E2E', function () {
    
    it('denies unauthorized user from accessing profile', function () {
        $response = $this->getJson('/api/auth/me');
        
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });

    it('denies user without permission to read profile', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('read profile');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to update profile', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('update profile');
        
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/me', [
                'name' => 'Updated Name',
                'email' => $user->email,
            ]);
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to read category', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('read category');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/master/category');
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to create member', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('create member');
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/master/member', [
                'name' => 'New Member',
                'email' => 'member@example.com',
            ]);
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to create selling', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('create selling');
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/transaction/selling', [
                'selling_details' => [],
                'total_price' => 0,
            ]);
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to read about', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('read about');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/about');
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('denies user without permission to manage settings', function () {
        $user = User::first();
        $user->update(['is_owner' => false]);
        $user->syncRoles([]);
        $user->revokePermissionTo('manage settings');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/setting/currency');
        
        expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
    });

    it('allows user with correct permissions to access resource', function () {
        $user = User::first();
        // Ensure user has permission
        $user->givePermissionTo('read profile');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');
        
        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('permission inheritance works across role hierarchy', function () {
        $user = User::first();
        $role = $user->roles->first();
        
        // Give permission to role
        $role->givePermissionTo('read category');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/master/category');
        
        expect($response->status())->toBe(Response::HTTP_OK);
    });
});
