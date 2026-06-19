<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->double('platform_fee_percent')->default(1.00);
            $table->string('payout_schedule', 20)->default('manual');
            $table->string('midtrans_client_key')->nullable();
            $table->string('midtrans_server_key')->nullable();
            $table->string('midtrans_merchant_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_code', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->dropColumn([
                'platform_fee_percent',
                'payout_schedule',
                'midtrans_client_key',
                'midtrans_server_key',
                'midtrans_merchant_id',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_code',
            ]);
        });
    }
};