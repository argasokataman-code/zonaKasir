<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // shift_no unik per tenant (anti duplikat concurrent open)
        DB::statement("
            CREATE UNIQUE INDEX cash_drawers_shift_no_unique_per_tenant
            ON cash_drawers (tenant_id, shift_no)
            WHERE shift_no IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cash_drawers_shift_no_unique_per_tenant');
    }
};
