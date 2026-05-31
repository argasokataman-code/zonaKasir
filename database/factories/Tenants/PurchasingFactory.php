<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Purchasing;
use App\Models\Tenants\Supplier;
use App\Models\Tenants\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Purchasing>
 */
class PurchasingFactory extends Factory
{
    protected $model = Purchasing::class;

    public function definition(): array
    {
        $supplier = Supplier::first() ?? Supplier::factory()->create();
        $user = User::first() ?? User::factory()->create();

        return [
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'payment_method_id' => null,
            'number' => 'PO-' . $this->faker->unique()->numerify('######'),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'image' => null,
            'total_initial_price' => $this->faker->numberBetween(100000, 5000000),
            'total_selling_price' => $this->faker->numberBetween(150000, 6000000),
            'tax' => $this->faker->randomFloat(2, 0, 10),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'payment_status' => $this->faker->boolean(),
            'approved_at' => null,
        ];
    }
}
