<?php

namespace Database\Seeders;

use App\Models\Tenants\PaymentMethod;
use Illuminate\Database\Seeder;

class DigitalPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'GoPay', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/gopay.png'],
            ['name' => 'QRIS', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/qris.png'],
            ['name' => 'Bank Transfer', 'is_cash' => false, 'is_debit' => true, 'is_credit' => false, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/bank-transfer.png'],
            ['name' => 'Credit Card', 'is_cash' => false, 'is_debit' => false, 'is_credit' => true, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/credit-card.png'],
            ['name' => 'ShopeePay', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/shopeepay.png'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
