<?php

namespace Database\Seeders;

use App\Models\Tenants\PaymentMethod;
use Illuminate\Database\Seeder;

class DigitalPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'GoPay', 'payment_type' => 'gopay', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/gopay.png'],
            ['name' => 'QRIS', 'payment_type' => 'qris', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/qris.png'],
            ['name' => 'Bank Transfer', 'payment_type' => 'bank_transfer', 'is_cash' => false, 'is_debit' => true, 'is_credit' => false, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/bank-transfer.png'],
            ['name' => 'Credit Card', 'payment_type' => 'credit_card', 'is_cash' => false, 'is_debit' => false, 'is_credit' => true, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/credit-card.png'],
            ['name' => 'ShopeePay', 'payment_type' => 'shopeepay', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => true, 'icon' => 'assets/images/payment-methods/shopeepay.png'],
            ['name' => 'Indomaret', 'payment_type' => 'indomaret', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/indomaret.png'],
            ['name' => 'Alfamart', 'payment_type' => 'alfamart', 'is_cash' => false, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/alfamart.png'],
            ['name' => 'Kredivo', 'payment_type' => 'kredivo', 'is_cash' => false, 'is_debit' => false, 'is_credit' => true, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/kredivo.png'],
            ['name' => 'Akulaku', 'payment_type' => 'akulaku', 'is_cash' => false, 'is_debit' => false, 'is_credit' => true, 'is_wallet' => false, 'icon' => 'assets/images/payment-methods/akulaku.png'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
