<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\Member;
use App\Models\Tenants\Receivable;
use App\Models\Tenants\Selling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Receivable>
 */
class ReceivableFactory extends Factory
{
    protected $model = Receivable::class;

    public function definition(): array
    {
        $member = Member::first() ?? Member::factory()->create();
        $selling = Selling::first() ?? Selling::factory()->create();
        $totalDebt = $this->faker->numberBetween(50000, 1000000);

        return [
            'member_id' => $member->id,
            'selling_id' => $selling->id,
            'total_receivable' => $totalDebt,
            'rest_receivable' => $totalDebt,
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'last_billing_date' => null,
            'total_billing_via_whatsapp' => 0,
            'status' => false,
        ];
    }
}
