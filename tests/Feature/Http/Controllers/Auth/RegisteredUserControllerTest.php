<?php

use App\Models\Tenants\User;
use App\Notifications\DomainCreated;
use App\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    try {
        $driver = DB::getDriverName();
    } catch (\Throwable $e) {
        $driver = 'sqlite';
    }

    if ($driver === 'mysql') {
        DB::statement('DROP DATABASE IF EXISTS lakasir_tokotest');
    }
});
it('user can create the tenant account', function () {
    Notification::fake();

    $data = [
        'name' => 'tokotest',
        'domain' => 'tokotest.localhost.com',
        'email' => 'test@mail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'business_type' => 'fnb',
    ];
    $response = postJson('/api/domain/register', $data);

    $response->assertStatus(200);

    $tenant = Tenant::find('tokotest');

    $this->assertDatabaseHas('tenants', [
        'id' => 'tokotest',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@mail.com',
        'is_owner' => true,
    ]);
});

it('user cannot create the tenant business_type not in list', function () {
    $data = [
        'domain' => 'tokotest.localhost.com',
        'business_type' => 'test',
    ];
    $response = postJson('/api/domain/register', $data);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['business_type']);
});

it('user can not create the tenant account without required fields', function () {
    $data = [
        'name' => 'test',
        'domain' => 'test',
    ];
    $response = postJson('/api/domain/register', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password', 'business_type']);
});

it('user can not create the tenant with invalid busines type', function () {
    $data = [
        'domain' => 'test.localhost.com',
        'business_type' => 'test',
    ];
    $response = postJson('/api/domain/register', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['business_type']);
});

test('user can not create the tenant when the other business type null if business type is other', function () {
    $data = [
        'domain' => 'test.localhost.com',
        'business_type' => 'other',
    ];
    $response = postJson('/api/domain/register', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['other_business_type']);
});

afterAll(function () {
    try {
        $driver = DB::getDriverName();
    } catch (\Throwable $e) {
        $driver = 'sqlite';
    }

    if ($driver === 'mysql') {
        DB::statement('DROP DATABASE IF EXISTS lakasir_tokotest');
    }
});
