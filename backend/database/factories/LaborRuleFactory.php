<?php

namespace Database\Factories;

use App\Models\LaborRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaborRule>
 */
class LaborRuleFactory extends Factory
{
    protected $model = LaborRule::class;

    public function definition(): array
    {
        return [
            'vigente_desde' => now()->startOfYear()->toDateString(),
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
            'recargo_nocturno' => 35.00,
            'recargo_dominical' => 75.00,
            'extra_diurna' => 25.00,
            'extra_nocturna' => 75.00,
            'porcentaje_salud' => 4.00,
            'porcentaje_pension' => 4.00,
            'horas_max_diarias' => 8.00,
        ];
    }
}
