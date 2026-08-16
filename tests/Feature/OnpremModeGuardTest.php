<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks public register route on on-prem mode', function () {
    config(['onprem.mode' => true]);

    $this->get('/auth/register')->assertRedirect('/member/login');
});

it('blocks google login route on on-prem mode', function () {
    config(['onprem.mode' => true]);

    $this->get('/auth/google/redirect')->assertRedirect('/member/login');
});

it('blocks register API on on-prem mode', function () {
    config(['onprem.mode' => true]);

    $this->postJson('/api/domain/register', [
        'name' => 'Toko Baru',
        'email' => 'baru@toko.id',
        'password' => 'rahasia123',
        'business_type' => 'retail',
    ])->assertRedirect('/member/login');
});

it('allows register routes when not on-prem', function () {
    config(['onprem.mode' => false]);

    $this->get('/auth/register')->assertOk();
});
