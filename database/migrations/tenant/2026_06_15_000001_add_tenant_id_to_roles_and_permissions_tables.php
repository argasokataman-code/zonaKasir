<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'roles',
        'permissions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->string('tenant_id')->nullable();
                $t->index('tenant_id');
            });
        }

        // Backfill roles.tenant_id from users who have each role
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles') && Schema::hasTable('users')) {
            $roles = DB::table('roles')
                ->whereNull('tenant_id')
                ->get();

            foreach ($roles as $role) {
                $tenantId = DB::table('model_has_roles')
                    ->join('users', 'model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.role_id', $role->id)
                    ->where('model_has_roles.model_type', 'App\\Models\\Tenants\\User')
                    ->value('users.tenant_id');

                if ($tenantId) {
                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update(['tenant_id' => $tenantId]);
                }
            }
        }

        // Backfill permissions.tenant_id from roles that have each permission
        if (Schema::hasTable('permissions') && Schema::hasTable('role_has_permissions') && Schema::hasTable('roles')) {
            $permissions = DB::table('permissions')
                ->whereNull('tenant_id')
                ->get();

            foreach ($permissions as $permission) {
                $tenantId = DB::table('role_has_permissions')
                    ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
                    ->where('role_has_permissions.permission_id', $permission->id)
                    ->value('roles.tenant_id');

                if ($tenantId) {
                    DB::table('permissions')
                        ->where('id', $permission->id)
                        ->update(['tenant_id' => $tenantId]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['tenant_id']);
                $t->dropColumn('tenant_id');
            });
        }
    }
};
