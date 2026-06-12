<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 'inv-' . $this->faker->unique()->randomNumber(6),
            'subscription_id' => Subscription::factory(),
            'number' => 'INV-' . strtoupper($this->faker->unique()->bothify('??####')),
            'amount' => $this->faker->numberBetween(50000, 500000),
            'status' => 'pending',
            'payment_method' => 'manual',
            'notes' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'notes' => 'Payment failed: insufficient balance',
        ]);
    }
}