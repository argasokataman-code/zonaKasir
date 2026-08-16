<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-2.3: tables capacity/zone/sort_order + unique name per tenant
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()
                ->comment('jumlah kursi');
            $table->string('zone', 50)->nullable()
                ->comment('indoor, outdoor, vip, dll');
            $table->unsignedInteger('sort_order')->default(0);
        });

        DB::statement("
            CREATE UNIQUE INDEX tables_number_unique_per_tenant
            ON tables (tenant_id, number)
            WHERE deleted_at IS NULL
        ");

        // FR-1.5: cancel audit trail
        Schema::table('sellings', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'zone', 'sort_order']);
        });
        DB::statement('DROP INDEX IF EXISTS tables_number_unique_per_tenant');
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancelled_at', 'cancel_reason']);
        });
    }
};
