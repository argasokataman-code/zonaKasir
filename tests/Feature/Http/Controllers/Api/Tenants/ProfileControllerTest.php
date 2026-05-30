<?php

use App\Models\Tenants\User;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

test('user can get the profile', function () {
    $user = User::first();

    $response = actingAs($user)->getJson('/api/auth/me');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'address',
                'photo',
                'locale',
                'roles',
                'created_at',
                'updated_at',
            ],
        ]);
});

test('user can update the profile', function () {
    $user = User::first();

    $response = actingAs($user)->putJson('/api/auth/me', [
        'name' => 'John Doe',
        'email' => 'updated@mail.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'updated@mail.com',
    ]);
});

test('update profile returns updated profile data', function () {
    $user = User::first();

    $response = actingAs($user)->putJson('/api/auth/me', [
        'name' => 'Jane Smith',
        'email' => 'jane@mail.com',
        'phone' => '081234567890',
        'address' => 'Jl. Test No. 123',
        'locale' => 'id',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
            ],
        ])
        ->assertJsonPath('data.name', 'Jane Smith')
        ->assertJsonPath('data.email', 'jane@mail.com');
});
