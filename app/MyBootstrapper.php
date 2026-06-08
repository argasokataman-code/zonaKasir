<?php

namespace App;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class MyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        // If the tenant database exists but has not been migrated yet, migrate it lazily.
        // This avoids fatal missing-table errors when a tenant DB was created without
        // tenant migration data present.
        if (! Schema::hasTable('users')) {
            Artisan::call('migrate', [
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
                '--database' => 'tenant',
                '--force' => true,
            ]);
        }
    }

    public function revert()
    {
    }
}
