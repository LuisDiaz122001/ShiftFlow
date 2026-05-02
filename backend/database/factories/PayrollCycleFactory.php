<?php

namespace Database\Factories;

use App\Models\PayrollCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollCycle>
 */
class PayrollCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->endOfMonth()->toDateString(),
            'fecha_pago' => now()->endOfMonth()->toDateString(),
            'estado' => \App\Models\PayrollCycle::STATUS_OPEN,
        ];
    }
}
