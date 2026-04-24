<?php

namespace Tests\Feature\Api\V1;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LaborRule::create([
            'nombre' => 'Regla General API',
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

    public function test_can_create_shift_via_api(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);
        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'nombre' => 'Juan API',
            'documento' => 'PAYROLL-API-001',
        ]);
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2000000,
            'fecha_inicio' => '2026-01-01',
            'estado' => 'activo',
        ]);

        PayrollCycle::create([
            'fecha_inicio' => '2026-04-16',
            'fecha_fin' => '2026-04-30',
            'fecha_pago' => '2026-04-30',
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $response = $this->postJson('/api/v1/shifts', [
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-20 08:00:00',
            'fecha_fin' => '2026-04-20 16:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'calculation']]);
    }

    public function test_can_process_payroll_cycle_via_api(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);
        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'nombre' => 'Juan API',
            'documento' => 'PAYROLL-API-002',
        ]);
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 1000000,
            'fecha_inicio' => '2026-01-01',
            'estado' => 'activo',
        ]);

        $cycle = PayrollCycle::create([
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-15',
            'fecha_pago' => '2026-04-15',
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        $user = User::factory()->create();
        Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => '2026-04-05 08:00:00',
            'fecha_fin' => '2026-04-05 16:00:00',
            'payroll_cycle_id' => $cycle->id,
        ]);

        // Debemos asegurar que el turno está calculado antes de procesar el ciclo
        // En una app real esto pasaría secuencialmente. 
        // Aquí forzamos el cálculo técnico manual para el test si no usamos el action.
        app(\App\Actions\CalculateShiftAction::class)->execute(Shift::first());

        $response = $this->postJson("/api/v1/payroll-cycles/{$cycle->id}/process");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', PayrollCycle::STATUS_GENERATED);

        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee->id,
            'payroll_cycle_id' => $cycle->id,
        ]);
    }

    public function test_cannot_process_closed_cycle(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $cycle = PayrollCycle::create([
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-15',
            'fecha_pago' => '2026-04-15',
            'estado' => PayrollCycle::STATUS_CLOSED,
        ]);

        $response = $this->postJson("/api/v1/payroll-cycles/{$cycle->id}/process");

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_can_list_payrolls_with_filters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);
        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'nombre' => 'Juan',
            'documento' => 'PAYROLL-API-003',
        ]);
        $cycle = PayrollCycle::create([
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-15',
            'fecha_pago' => '2026-04-15',
            'estado' => 'open'
        ]);
        
        Payroll::create([
            'employee_id' => $employee->id,
            'payroll_cycle_id' => $cycle->id,
            'salario_base_pagado' => 1000,
            'recargos_pagados' => 0,
            'deduccion_salud' => 40,
            'deduccion_pension' => 40,
            'total_pagado' => 1000,
            'neto_pagado' => 920,
            'tipo_pago' => 15,
            'fecha_pago' => '2026-04-15',
        ]);

        $response = $this->getJson("/api/v1/payrolls?employee_id={$employee->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
