<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LaborRule;
use App\Models\Shift;
use App\Models\User;
use App\Services\PayrollCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_regular_and_extra_daytime_hours(): void
    {
        $this->createBaseRule();

        $user = $this->createUser();
        $employee = $this->createEmployee($user);
        $this->createContract($employee);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-13 08:00:00',
            'fecha_fin' => '2026-04-13 17:00:00',
        ]);

        $result = app(PayrollCalculatorService::class)->calculate($shift);

        $this->assertSame(92500.0, $result['total']);
        $this->assertSame(8.0, $result['horas_diurnas']);
        $this->assertSame(0.0, $result['horas_nocturnas']);
        $this->assertSame(1.0, $result['horas_extra_diurnas']);
        $this->assertSame(0.0, $result['horas_extra_nocturnas']);
        $this->assertCount(2, $result['detalle']);
        $this->assertSame('normal', $result['detalle'][0]['tipo_hora']);
        $this->assertSame('extra', $result['detalle'][1]['tipo_hora']);
    }

    public function test_it_resets_overtime_limit_when_shift_crosses_midnight(): void
    {
        $this->createBaseRule();

        $user = $this->createUser();
        $employee = $this->createEmployee($user);
        $this->createContract($employee);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-13 18:00:00',
            'fecha_fin' => '2026-04-14 04:00:00',
        ]);

        $result = app(PayrollCalculatorService::class)->calculate($shift);

        $this->assertSame(131500.0, $result['total']);
        $this->assertSame(1.0, $result['horas_diurnas']);
        $this->assertSame(9.0, $result['horas_nocturnas']);
        $this->assertSame(0.0, $result['horas_extra_diurnas']);
        $this->assertSame(0.0, $result['horas_extra_nocturnas']);
        $this->assertCount(3, $result['detalle']);
    }

    public function test_it_applies_special_day_surcharge_only_once_for_holiday_sunday(): void
    {
        $this->createBaseRule();

        Holiday::query()->create([
            'fecha' => '2026-04-19',
            'nombre' => 'Domingo festivo de prueba',
        ]);

        $user = $this->createUser();
        $employee = $this->createEmployee($user);
        $this->createContract($employee);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-19 18:00:00',
            'fecha_fin' => '2026-04-19 20:00:00',
        ]);

        $result = app(PayrollCalculatorService::class)->calculate($shift);

        $this->assertSame(38500.0, $result['total']);
        $this->assertSame(1.0, $result['horas_diurnas']);
        $this->assertSame(1.0, $result['horas_nocturnas']);
        $this->assertSame(75.0, $result['detalle'][0]['recargos_aplicados']['recargo_festivo']);
        $this->assertArrayNotHasKey('recargo_dominical', $result['detalle'][0]['recargos_aplicados']);
        $this->assertSame(110.0, $result['detalle'][1]['porcentaje_recargo_total']);
    }

    public function test_it_uses_the_fixed_240_hour_monthly_base_to_resolve_hourly_rate(): void
    {
        $this->createBaseRule([
            'horas_max_diarias' => 7.5,
        ]);

        $user = $this->createUser();
        $employee = $this->createEmployee($user);
        $this->createContract($employee);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-15 10:00:00',
            'fecha_fin' => '2026-04-15 11:00:00',
        ]);

        $result = app(PayrollCalculatorService::class)->calculate($shift);

        $this->assertSame(10000.0, $result['total']);
        $this->assertSame(10000.0, $result['detalle'][0]['valor_hora_base']);
    }

    public function test_it_calculates_a_long_shift_from_four_pm_to_seven_am(): void
    {
        $this->createBaseRule();

        $user = $this->createUser();
        $employee = $this->createEmployee($user);
        $this->createContract($employee);

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-15 16:00:00',
            'fecha_fin' => '2026-04-16 07:00:00',
        ]);

        $result = app(PayrollCalculatorService::class)->calculate($shift);

        $this->assertSame(188500.0, $result['total']);
        $this->assertSame(4.0, $result['horas_diurnas']);
        $this->assertSame(11.0, $result['horas_nocturnas']);
        $this->assertSame(0.0, $result['horas_extra_diurnas']);
        $this->assertSame(0.0, $result['horas_extra_nocturnas']);
        $this->assertCount(4, $result['detalle']);
        $this->assertSame('2026-04-15 16:00:00', $result['detalle'][0]['inicio']);
        $this->assertSame('2026-04-16 07:00:00', $result['detalle'][3]['fin']);
    }

    private function createBaseRule(array $overrides = []): LaborRule
    {
        return LaborRule::query()->create(array_merge([
            'vigente_desde' => '2026-01-01',
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '19:00:00',
            'recargo_nocturno' => 35,
            'recargo_dominical' => 75,
            'extra_diurna' => 25,
            'extra_nocturna' => 75,
            'horas_max_diarias' => 8,
        ], $overrides));
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Operario ShiftFlow',
            'email' => 'operario@example.com',
            'password' => 'secret123',
        ]);
    }

    private function createEmployee(User $user): Employee
    {
        return Employee::query()->create([
            'user_id' => $user->id,
            'nombre' => $user->name,
            'estado' => Employee::ESTADO_ACTIVO,
        ]);
    }

    private function createContract(Employee $employee, array $overrides = []): Contract
    {
        return Contract::query()->create(array_merge([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => null,
            'estado' => Contract::ESTADO_ACTIVO,
        ], $overrides));
    }
}
