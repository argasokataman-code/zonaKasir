<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use App\Models\Tenants\Member;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member Management E2E', function () {
    
    it('can list members with pagination', function () {
        Member::factory(15)->create();
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/master/member');
        
        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json())->toHaveKey('data');
        expect($response->json())->toHaveKey('pagination');
    });

    it('can create new member', function () {
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/master/member', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+62812345678',
                'address' => 'Jakarta',
            ]);
        
        expect($response->status())->toBe(Response::HTTP_CREATED);
        expect($response->json('data.name'))->toBe('John Doe');
    });

    it('can read single member details', function () {
        $member = Member::factory()->create();
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/master/member/{$member->id}");
        
        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json('data.id'))->toBe($member->id);
    });

    it('can update member information', function () {
        $member = Member::factory()->create();
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/master/member/{$member->id}", [
                'name' => 'Updated Name',
                'phone' => '+628987654321',
            ]);
        
        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json('data.name'))->toBe('Updated Name');
    });

    it('can delete member', function () {
        $member = Member::factory()->create();
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/master/member/{$member->id}");
        
        expect($response->status())->toBe(Response::HTTP_OK);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    });

    it('returns validation errors for invalid member data', function () {
        $user = User::first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/master/member', [
                'name' => '', // Invalid - required
            ]);
        
        expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
        expect($response->json())->toHaveKey('errors');
    });

    it('enforces authorization on member access', function () {
        $member = Member::factory()->create();
        
        $response = $this->getJson("/api/master/member/{$member->id}");
        
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
