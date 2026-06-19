<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to tenants table (for existing DBs that have the table but lack these columns)
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (! Schema::hasColumn('tenants', 'data')) {
                    $table->longText('data')->nullable();
                }
                if (! Schema::hasColumn('tenants', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('tenants', 'suspended_at')) {
                    $table->timestamp('suspended_at')->nullable();
                }
                if (! Schema::hasColumn('tenants', 'suspension_reason')) {
                    $table->text('suspension_reason')->nullable();
                }
            });
        }

        // Create domains table if it doesn't exist
        if (! Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->string('tenant_id');
                $table->timestamps();
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['data', 'is_active', 'suspended_at', 'suspension_reason']);
        });

        Schema::dropIfExists('domains');
    }
};
