<?php

namespace Tests;

use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Models\Subscription;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

trait RefreshDatabaseWithTenant
{
    use RefreshDatabase {
        beginDatabaseTransaction as parentBeginDatabaseTransaction;
    }

    public function beginDatabaseTransaction()
    {
        $this->parentBeginDatabaseTransaction();

        $tenantId = 'toko_testing';

        $this->user = User::factory()->create([
            'tenant_id' => $tenantId,
            'email' => 'admin_' . uniqid() . '@tokotesting.com',
            'is_owner' => true,
        ]);

        About::create([
            'tenant_id' => $tenantId,
            'shop_name' => 'Toko Testing',
            'business_type' => 'retail',
        ]);

        try {
            TenantContext::set($tenantId);
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'PaymentMethodSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'DigitalPaymentMethodSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);
        } catch (\Throwable $e) {
        }

        Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
        ]);
    }
}
