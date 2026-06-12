<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abouts', function (Blueprint $table) {
            $table->double('platform_fee_percent')->default(1.00)->after('currency');
            $table->string('payout_schedule', 20)->default('manual')->after('platform_fee_percent');
            $table->string('midtrans_client_key')->nullable()->after('payout_schedule');
            $table->string('midtrans_server_key')->nullable()->after('midtrans_client_key');
            $table->string('midtrans_merchant_id')->nullable()->after('midtrans_server_key');
            $table->string('bank_name')->nullable()->after('midtrans_merchant_id');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('bank_code', 10)->nullable()->after('bank_account_number');
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