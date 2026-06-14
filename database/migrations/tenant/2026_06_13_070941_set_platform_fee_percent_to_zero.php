<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenants\About;

return new class extends Migration
{
    public function up(): void
    {
        // Reset platform fee to 0% for all existing tenants
        About::withoutGlobalScopes()->where('platform_fee_percent', '>', 0)->update(['platform_fee_percent' => 0]);

        // Change column default to 0 for future records
        Schema::table('abouts', function (Blueprint $table) {
            $table->double('platform_fee_percent')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->double('platform_fee_percent')->default(1.00)->change();
        });
    }
};
