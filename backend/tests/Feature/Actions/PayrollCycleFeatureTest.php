<?php

namespace Tests\Feature\Actions;

use App\Actions\CalculateShiftAction;
use App\Actions\GeneratePayrollAction;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PayrollCycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LaborRule::create([
            'nombre' => 'Regla General 2026',
            'vigente_desde' => '2026-01-01',
            'horas_max_diarias' => 8,
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
            'recargo_nocturno' => 35,
            'recargo_dominical' => 75,
            'extra_diurna' => 25,
            'extra_nocturna' => 75,
            'porcentaje_salud' => 4.00,
            'porcentaje_pension' => 4.00,
        ]);
    }

    public function test_it_fails_to_calculate_shift_if_no_cycle_exists(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create(['nombre' => 'Juan']);
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $shift = Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => Carbon::parse('2026-04-20 08:00:00'),
            'fecha_fin' => Carbon::parse('2026-04-20 16:00:00'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No existe un ciclo de nómina abierto');

        app(CalculateShiftAction::class)->execute($shift);
    }

    public function test_it_calculates_shift_when_cycle_is_open(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create(['nombre' => 'Juan']);
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        PayrollCycle::create([
            'fecha_inicio' => '2026-04-16',
            'fecha_fin' => '2026-04-30',
            'fecha_pago' => '2026-04-30',
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $shift = Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => Carbon::parse('2026-04-20 08:00:00'),
            'fecha_fin' => Carbon::parse('2026-04-20 16:00:00'),
        ]);

        app(CalculateShiftAction::class)->execute($shift);

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'payroll_cycle_id' => 1,
        ]);
    }

    public function test_it_generates_payroll_with_deductions_for_an_employee(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create(['nombre' => 'Juan']);
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000, // 10k/hora
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $cycle = PayrollCycle::create([
            'fecha_inicio' => '2026-04-16',
            'fecha_fin' => '2026-04-30',
            'fecha_pago' => '2026-04-30',
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $action = app(CalculateShiftAction::class);

        // Turno 1: 8h = 80.000
        $shift1 = Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => Carbon::parse('2026-04-20 08:00:00'),
            'fecha_fin' => Carbon::parse('2026-04-20 16:00:00'),
        ]);
        $action->execute($shift1);

        // Turno 2: 2h Nocturnas (21:00 - 23:00) 
        // 2h * 10k = 20k (Base - ya en fixed) 
        // 2h * 10k * 0.35 = 7k (Recargo Nocturno - Extra)
        $shift2 = Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => Carbon::parse('2026-04-21 21:00:00'),
            'fecha_fin' => Carbon::parse('2026-04-21 23:00:00'),
        ]);
        $action->execute($shift2);

        // Generar Nómina
        /** @var Payroll $payroll */
        $payroll = app(\App\Actions\GeneratePayrollAction::class)->execute($employee, $cycle);

        $this->assertNotNull($payroll);

        // Devengado Total: 1.2M (quincena) + 7k (recargo nocturno) = 1.207.000
        $expectedIbc = 1207000.00;
        
        // Deducciones: 4% de cada una
        $expectedSalud = round($expectedIbc * 0.04, 2); // 48.280
        $expectedPension = round($expectedIbc * 0.04, 2); // 48.280
        $expectedNeto = round($expectedIbc - ($expectedSalud + $expectedPension), 2); // 1.110.440

        $this->assertEquals(1200000.00, (float) $payroll->salario_base_pagado);
        $this->assertEquals(7000.00, (float) $payroll->recargos_pagados);
        $this->assertEquals($expectedSalud, (float) $payroll->deduccion_salud);
        $this->assertEquals($expectedPension, (float) $payroll->deduccion_pension);
        $this->assertEquals($expectedNeto, (float) $payroll->neto_pagado);
    }
}
