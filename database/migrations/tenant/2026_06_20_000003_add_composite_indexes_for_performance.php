<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['tenant_id', 'product_id', 'type', 'is_ready'], 'stocks_tenant_product_type_ready_index');
        });
    }

    public function down(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('stocks_tenant_product_type_ready_index');
        });
    }
};
