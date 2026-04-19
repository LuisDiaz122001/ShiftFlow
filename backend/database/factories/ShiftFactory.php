<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
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
            'user_id' => \App\Models\User::factory(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addHours(8),
            'status' => \App\Models\Shift::STATUS_PENDING,
        ];
    }
}
