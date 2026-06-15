<?php

use App\Models\Tenants\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('tenants', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
