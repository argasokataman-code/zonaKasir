<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->string('menu_token', 32)->nullable()->unique()
                ->comment('public token utk halaman menu digital');
        });
    }

    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->dropUnique(['menu_token']);
            $table->dropColumn('menu_token');
        });
    }
};
