<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'salario_base' => 2400000.00,
            'fecha_inicio' => now()->startOfYear(),
            'fecha_fin' => null,
            'estado' => Contract::ESTADO_ACTIVO,
        ];
    }
}
