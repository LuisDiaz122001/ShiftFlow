<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkOut = (clone $checkIn)->modify('+8 hours');
        $totalHours = 8.00;

        return [
            'employee_id' => Employee::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_hours' => $totalHours,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indica que la asistencia está aprobada.
     */
    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indica que la asistencia está rechazada.
     */
    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indica que la asistencia está pendiente.
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
