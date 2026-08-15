<?php

use App\Models\OnpremInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects heartbeat without token', function () {
    $response = $this->postJson('/api/v1/onprem/heartbeat', [
        'instance_id' => 'abc-123',
    ]);

    $response->assertStatus(401);
});

it('registers onprem instance via heartbeat', function () {
    config(['onprem.heartbeat.token' => 'secret-token']);

    $response = $this->postJson('/api/v1/onprem/heartbeat', [
        'instance_id' => 'abc-123',
        'domain' => 'tokobudi.zona.id',
        'license_key' => 'ZONA-XYZ',
        'app_version' => '1.2.3',
        'php_version' => '8.4',
    ], ['X-Onprem-Token' => 'secret-token']);

    $response->assertOk()->assertJson(['ok' => true]);

    $this->assertDatabaseHas('onprem_instances', [
        'instance_id' => 'abc-123',
        'domain' => 'tokobudi.zona.id',
        'license_key' => 'ZONA-XYZ',
        'app_version' => '1.2.3',
    ]);

    $instance = OnpremInstance::where('instance_id', 'abc-123')->first();
    expect($instance->status())->toBe('online');
    expect($instance->last_seen_at)->not->toBeNull();
});

it('upserts existing instance instead of duplicating', function () {
    config(['onprem.heartbeat.token' => 'secret-token']);

    OnpremInstance::create([
        'instance_id' => 'abc-123',
        'domain' => 'old.zona.id',
        'last_seen_at' => now()->subDays(10),
    ]);

    $this->postJson('/api/v1/onprem/heartbeat', [
        'instance_id' => 'abc-123',
        'domain' => 'new.zona.id',
    ], ['X-Onprem-Token' => 'secret-token'])->assertOk();

    expect(OnpremInstance::where('instance_id', 'abc-123')->count())->toBe(1);
    expect(OnpremInstance::where('instance_id', 'abc-123')->first()->domain)->toBe('new.zona.id');
});
