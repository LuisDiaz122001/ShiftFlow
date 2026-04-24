<?php

namespace Tests\Feature;

use App\Actions\CalculateShiftAction;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\PayrollCycle;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateShiftActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_and_persists_a_shift_using_the_active_contract(): void
    {
        LaborRule::query()->create([
            'vigente_desde' => '2026-01-01',
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '19:00:00',
            'recargo_nocturno' => 35,
            'recargo_dominical' => 75,
            'extra_diurna' => 25,
            'extra_nocturna' => 75,
            'horas_max_diarias' => 8,
        ]);

        $user = User::query()->create([
            'name' => 'Empleado Flujo',
            'email' => 'feature@example.com',
            'password' => 'secret123',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'nombre' => $user->name,
            'documento' => 'CALC-FEATURE-001',
            'activo' => true,
        ]);

        Contract::query()->create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        PayrollCycle::query()->create([
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-15',
            'fecha_pago' => '2026-04-15',
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-13 08:00:00',
            'fecha_fin' => '2026-04-13 17:00:00',
        ]);

        app(CalculateShiftAction::class)->execute($shift);
        $calculation = $shift->refresh()->calculation;

        $this->assertDatabaseHas('shift_calculations', [
            'shift_id' => $shift->id,
            'valor_total' => 92500.00,
        ]);

        $this->assertNotNull($calculation);
        $this->assertSame(9.0, $calculation->total_hours);
        $this->assertSame(92500.0, $calculation->total_pay);
        $this->assertCount(2, $calculation->breakdown);
    }
}
