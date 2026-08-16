<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['tenant_id', 'key'], 'settings_tenant_id_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_tenant_id_key_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('key')->unique();
        });
    }
};
