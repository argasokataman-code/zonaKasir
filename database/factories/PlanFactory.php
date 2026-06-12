<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str()->slug($name) . '-' . $this->faker->unique()->randomNumber(3),
            'price_monthly' => $this->faker->numberBetween(50000, 500000),
            'price_yearly' => $this->faker->optional(0.8)->numberBetween(500000, 5000000),
            'max_stores' => $this->faker->numberBetween(1, 10),
            'max_users' => $this->faker->numberBetween(1, 50),
            'is_active' => true,
            'features' => $this->faker->randomElements([
                'pos', 'report', 'stock_management', 'member_management',
                'multi_store', 'api_access', 'export_csv', 'custom_print',
            ], $this->faker->numberBetween(2, 6)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => null,
            'max_stores' => 1,
            'max_users' => 1,
            'features' => ['pos', 'report'],
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn () => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price_monthly' => 500000,
            'price_yearly' => 5000000,
            'max_stores' => 99,
            'max_users' => 999,
            'features' => ['pos', 'report', 'stock_management', 'member_management', 'multi_store', 'api_access'],
        ]);
    }
}