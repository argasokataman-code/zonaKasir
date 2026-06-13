<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original create-licenses migration shipped tenant_id as NOT NULL.
 * Environments that ran it before the fix (e.g. the production VPS under
 * MySQL strict mode) can't store unassigned keys. This makes the column
 * nullable everywhere; it's a no-op where the create migration already
 * defines it as nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left as nullable on purpose — reverting could break unassigned keys.
    }
};
