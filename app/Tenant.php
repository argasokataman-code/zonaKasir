<?php

namespace App;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    protected $guarded = ['id'];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Run a callback within this tenant's database context.
     *
     * ⚠️ SHARED DB TENANT (tenancy_db_name IS NULL):
     *    Only 1 database ("zonakasir"). All tenants share it, filtered by `tenant_id` column.
     *    `run()` does NOT switch connection. Callback runs on default connection.
     *
     * ⛔ ISOLATED DB TENANT (tenancy_db_name IS SET):
     *    Each tenant has its own database. `run()` switches connection to that database.
     *    Used for legacy/migrated tenants only.
     *
     * Compatible with stancl/tenancy's Tenant::run() API.
     */
    public function run(Closure $callback): mixed
    {
        if (!$this->tenancy_db_name) {
            // SHARED DATABASE — no connection switch, just run callback
            return $callback();
        }

        // ISOLATED DATABASE — switch to tenant-specific DB
        $originalConnection = config('database.default');
        $tenantDb = $this->tenancy_db_name;
        $currentDriver = config("database.connections.{$originalConnection}.driver");

        // Create temporary connection pointing to tenant DB
        $tenantConfig = [
            'driver' => $currentDriver,
            'host' => config("database.connections.{$originalConnection}.host"),
            'port' => config("database.connections.{$originalConnection}.port"),
            'database' => $tenantDb,
            'username' => config("database.connections.{$originalConnection}.username"),
            'password' => config("database.connections.{$originalConnection}.password"),
            'prefix' => '',
            'prefix_indexes' => true,
        ];

        // Add driver-specific config
        if ($currentDriver === 'mysql') {
            $tenantConfig['unix_socket'] = config("database.connections.{$originalConnection}.unix_socket");
            $tenantConfig['charset'] = 'utf8mb4';
            $tenantConfig['collation'] = 'utf8mb4_unicode_ci';
            $tenantConfig['strict'] = false;
        } elseif ($currentDriver === 'pgsql') {
            $tenantConfig['search_path'] = 'public';
            $tenantConfig['sslmode'] = 'prefer';
        }

        config([
            'database.connections.tenant_runtime' => $tenantConfig,
        ]);
        config(['database.default' => 'tenant_runtime']);
        DB::purge('tenant_runtime');

        try {
            return $callback();
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge($originalConnection);
        }
    }

    public function domains()
    {
        return $this->hasMany(\App\Domain::class, 'tenant_id', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'tenant_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'tenant_id', 'id');
    }
}
