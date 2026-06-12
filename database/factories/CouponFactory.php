<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['percentage', 'nominal', 'trial_extension']);

        return [
            'code' => strtoupper($this->faker->unique()->bothify('???####')),
            'type' => $type,
            'value' => $type === 'trial_extension' ? null : $this->faker->numberBetween(1, 100),
            'trial_days' => $type === 'trial_extension' ? $this->faker->numberBetween(3, 30) : null,
            'max_redemptions' => $this->faker->optional(0.7)->numberBetween(1, 100),
            'used_count' => 0,
            'expires_at' => $this->faker->optional(0.6)->dateTimeBetween('+1 week', '+1 year'),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn () => [
            'type' => 'percentage',
            'value' => $this->faker->numberBetween(5, 50),
            'trial_days' => null,
        ]);
    }

    public function nominal(): static
    {
        return $this->state(fn () => [
            'type' => 'nominal',
            'value' => $this->faker->numberBetween(10000, 500000),
            'trial_days' => null,
        ]);
    }

    public function trialExtension(): static
    {
        return $this->state(fn () => [
            'type' => 'trial_extension',
            'value' => null,
            'trial_days' => $this->faker->numberBetween(3, 30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => [
            'max_redemptions' => 5,
            'used_count' => 5,
        ]);
    }
}