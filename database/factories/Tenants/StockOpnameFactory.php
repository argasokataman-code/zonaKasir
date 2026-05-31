<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\StockOpname;
use App\Models\Tenants\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\StockOpname>
 */
class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        $user = User::first() ?? User::factory()->create();

        return [
            'user_id' => $user->id,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'number' => 'SO-' . $this->faker->unique()->numerify('######'),
            'pic' => $this->faker->name(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'approved_at' => null,
        ];
    }
}
