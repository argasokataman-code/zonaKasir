<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#FF6600')->after('photo');
            $table->text('logo')->nullable()->after('primary_color');
            $table->boolean('dark_mode')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'logo', 'dark_mode']);
        });
    }
};
