<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Selling>
 */
class SellingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Selling::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $member = Member::first() ?? Member::factory()->create();
        $totalPrice = 100000;
        $payedMoney = $this->randPayedMoney(rand(0, 2));
        
        return [
            'member_id' => $member->id,
            'date' => now(),
            'payed_money' => $payedMoney,
            'money_changes' => $payedMoney - $totalPrice,
            'total_price' => $totalPrice,
            'total_qty' => rand(1, 5),
        ];
    }

    private function randPayedMoney($randIndex)
    {
        $payedOptionMoney = [200000, 500000, 100000];
        return $payedOptionMoney[$randIndex];
    }
}
