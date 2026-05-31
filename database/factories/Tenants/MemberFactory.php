<?php

namespace Database\Factories\Tenants;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'code' => $this->faker->unique()->numerify('CUS####'),
            'address' => $this->faker->streetAddress,
            'email' => $this->faker->unique()->safeEmail()
        ];
    }
}
