<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 'sub-' . $this->faker->unique()->randomNumber(6),
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
            'ends_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'trial_ends_at' => now()->subDays(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'cancelled_at' => now()->subDay(),
        ]);
    }
}