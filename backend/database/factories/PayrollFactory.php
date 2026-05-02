<?php

namespace Database\Factories;

use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employee::factory(),
            'payroll_cycle_id' => \App\Models\PayrollCycle::factory(),
            'salario_base_pagado' => 1000,
            'recargos_pagados' => 0,
            'total_pagado' => 1000,
            'neto_pagado' => 900,
            'tipo_pago' => 15,
            'fecha_pago' => now()->toDateString(),
            'period_start' => now()->subDays(15)->toDateString(),
            'period_end' => now()->toDateString(),
            'estado' => \App\Models\Payroll::STATUS_PENDING,
            'total_hours' => 8,
            'hourly_rate' => 100,
            'total_amount' => 1000,
        ];
    }
}
