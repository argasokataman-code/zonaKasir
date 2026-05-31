<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\Printer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenants\Printer>
 */
class PrinterFactory extends Factory
{
    protected $model = Printer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Printer',
            'driver' => $this->faker->randomElement(['escpos', 'cups', 'windows']),
            'port' => $this->faker->randomElement(['9100', '515', null]),
            'ip_address' => $this->faker->optional()->localIpv4(),
        ];
    }
}
