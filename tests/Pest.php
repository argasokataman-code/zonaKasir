<?php

uses(
    Tests\DuskTestCase::class,
)->in('Browser');

use App\Models\Tenants\User;
use App\Models\Tenants\About;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
)->in('Feature', 'Unit');

function mockTenant(): User
{
    $tenantId = 'toko_testing';

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'email' => 'admin@tokotesting.com',
        'is_owner' => true,
    ]);

    About::create([
        'tenant_id' => $tenantId,
        'shop_name' => 'Toko Testing',
        'business_type' => 'retail',
    ]);

    return $user;
}
