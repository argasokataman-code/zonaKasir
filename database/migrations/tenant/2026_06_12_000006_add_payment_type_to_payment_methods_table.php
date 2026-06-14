<?php

use App\Models\Tenants\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('payment_type', 30)->nullable()->after('is_wallet')
                ->comment('Midtrans payment type: credit_card, gopay, shopeepay, qris, bank_transfer, indomaret, alfamart, kredivo, akulaku');
        });

        // Migrate existing data: set default payment_type based on flags
        foreach (PaymentMethod::withoutGlobalScopes()->get() as $method) {
            $type = match (true) {
                $method->is_cash => 'cash',
                $method->is_credit => 'credit_card',
                $method->is_debit => 'debit_card',
                $method->name === 'GoPay' => 'gopay',
                $method->name === 'QRIS' => 'qris',
                $method->name === 'ShopeePay' => 'shopeepay',
                $method->name === 'Bank Transfer' => 'bank_transfer',
                default => null,
            };
            if ($type) {
                $method->update(['payment_type' => $type]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
