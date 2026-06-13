<?php

namespace Tests;

use App\Models\Tenants\About;
use App\Models\Tenants\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait RefreshDatabaseWithTenant
{
    use RefreshDatabase {
        refreshDatabase as parentRefreshDatabase;
        beginDatabaseTransaction as parentBeginDatabaseTransaction;
    }

    public function beginDatabaseTransaction()
    {
        $tenantId = 'toko_testing';

        $this->user = User::factory()->create([
            'tenant_id' => $tenantId,
            'email' => 'admin@tokotesting.com',
            'is_owner' => true,
        ]);

        About::create([
            'tenant_id' => $tenantId,
            'shop_name' => 'Toko Testing',
            'business_type' => 'retail',
        ]);

        $this->parentBeginDatabaseTransaction();
    }
}
