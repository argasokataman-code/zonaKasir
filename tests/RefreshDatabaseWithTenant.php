<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

trait RefreshDatabaseWithTenant
{
    use RefreshDatabase {
        beginDatabaseTransaction as parentBeginDatabaseTransaction;
    }

    /**
     * We need to initialize tenancy BEFORE starting the database transaction,
     * otherwise it cannot find the tenant connection.
     * We use mockTenant() which handles database creation via RegisterTenant service.
     */
    public function beginDatabaseTransaction()
    {
        // Initialize tenant - this creates the database via RegisterTenant::create()
        $tenant = mockTenant();
        tenancy()->initialize($tenant);
        // Ensure domain exists and access it safely
        $domainModel = $tenant->domains()->first();
        $domain = $domainModel ? $domainModel->domain : ($tenant->id . '.' . config('tenancy.central_domains')[0]);
        URL::forceRootUrl("http://{$domain}");

        // Then start the database transaction
        $this->parentBeginDatabaseTransaction();
    }
}
