<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'welcomed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('welcomed_at')->nullable()->after('google_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'welcomed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('welcomed_at');
            });
        }
    }
};
