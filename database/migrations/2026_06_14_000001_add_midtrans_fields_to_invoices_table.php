<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->after('payment_method');
            $table->string('midtrans_transaction_id')->nullable()->after('midtrans_order_id');
            $table->text('midtrans_redirect_url')->nullable()->after('midtrans_transaction_id');
            $table->json('midtrans_notification_payload')->nullable()->after('midtrans_redirect_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'midtrans_order_id',
                'midtrans_transaction_id',
                'midtrans_redirect_url',
                'midtrans_notification_payload',
            ]);
        });
    }
};
