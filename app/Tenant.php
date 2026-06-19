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

        // Create temporary connection pointing to tenant DB
        config([
            'database.connections.tenant_runtime' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $tenantDb,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'unix_socket' => config('database.connections.mysql.unix_socket'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
            ],
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
