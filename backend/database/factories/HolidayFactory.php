<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'fecha' => $this->faker->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'nombre' => $this->faker->words(3, true),
        ];
    }
}
