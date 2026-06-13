<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->dropForeign(['selling_id']);
            $table->foreignId('selling_id')->nullable()->change();
            $table->foreign('selling_id')->references('id')->on('sellings')->onDelete('cascade');
            $table->json('cart_data')->nullable()->after('notification_payload');
        });
    }

    public function down(): void
    {
        Schema::table('midtrans_payments', function (Blueprint $table) {
            $table->dropForeign(['selling_id']);
            $table->foreignId('selling_id')->nullable(false)->change();
            $table->foreign('selling_id')->references('id')->on('sellings')->onDelete('cascade');
            $table->dropColumn('cart_data');
        });
    }
};
