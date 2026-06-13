<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\Member;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Member Portal Authentication', function () {
    it('member can login with correct credentials', function () {
        $member = Member::factory()->create([
            'email' => 'member@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/portal/login', [
            'email' => 'member@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/portal/dashboard');
        $this->assertAuthenticated('member');
    });

    it('member cannot login with wrong password', function () {
        $member = Member::factory()->create([
            'email' => 'member@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/portal/login', [
            'email' => 'member@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest('member');
    });

    it('guest is redirected when accessing protected page', function () {
        $response = $this->get('/portal/dashboard');

        expect($response->status())->toBe(302); // redirects to some login
    });

    it('authenticated member can logout', function () {
        $member = Member::factory()->create([
            'email' => 'member@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($member, 'member');

        $response = $this->post('/portal/logout');

        $response->assertRedirect('/portal/login');
        $this->assertGuest('member');
    });

    it('member cannot access staff admin panel', function () {
        $member = Member::factory()->create([
            'email' => 'member@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($member, 'member');

        $response = $this->get('/member/members');

        // Should redirect to staff login, not grant access
        $response->assertRedirect('/member/login');
    });
});
