<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard widgets filter: isPaid()->whereBetween('created_at')
        Schema::table('sellings', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_paid', 'created_at'], 'sellings_dashboard_index');
        });

        // TodaysBestSellingProduct: whereBetween(date)
        Schema::table('sellings', function (Blueprint $table) {
            $table->index(['tenant_id', 'date'], 'sellings_date_index');
        });

        // InventoryStats: whereHas stocks
        Schema::table('selling_details', function (Blueprint $table) {
            $table->index(['tenant_id', 'selling_id'], 'selling_details_selling_index');
        });
    }

    public function down(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropIndex('sellings_dashboard_index');
            $table->dropIndex('sellings_date_index');
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->dropIndex('selling_details_selling_index');
        });
    }
};
