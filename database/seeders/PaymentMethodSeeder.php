<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $data) {
            \App\Models\Tenants\PaymentMethod::updateOrCreate(
                ['payment_type' => $data['payment_type']],
                $data
            );
        }
    }

    private function data(): array
    {
        return [
            [
                'name' => 'Cash',
                'payment_type' => 'cash',
                'is_active' => true,
            ],
            [
                'name' => 'QRIS',
                'payment_type' => 'qris',
                'is_active' => true,
            ],
            [
                'name' => 'Piutang',
                'payment_type' => 'credit',
                'is_active' => true,
            ],
        ];
    }
}
