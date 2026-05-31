<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'contact_name' => $this->faker->optional()->name(),
            'email' => $this->faker->optional()->companyEmail(),
            'address' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->optional()->city(),
            'country' => $this->faker->optional()->country(),
            'postal_code' => $this->faker->optional()->postcode(),
        ];
    }
}
