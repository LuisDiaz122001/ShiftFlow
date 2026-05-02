<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use App\Models\Contract;
use App\Models\PayrollCycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $supervisor;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
        $this->employee = Employee::factory()->create();

        // Basic labor rule for calculations
        LaborRule::create([
            'vigente_desde' => '2020-01-01',
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
            'recargo_nocturno' => 0.35,
            'recargo_dominical' => 0.75,
            'extra_diurna' => 0.25,
            'extra_nocturna' => 0.75,
            'porcentaje_salud' => 4.00,
            'porcentaje_pension' => 4.00,
            'horas_max_diarias' => 8,
        ]);
    }

    public function test_cannot_duplicate_payroll_in_same_period(): void
    {
        $this->actingAs($this->admin);

        $periodStart = '2026-05-01';
        $periodEnd = '2026-05-15';

        // Active contract for the employee
        Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        // Create the cycle first to link the shift
        $cycle = PayrollCycle::where('fecha_inicio', $periodStart)
            ->where('fecha_fin', $periodEnd)
            ->first();

        if (! $cycle) {
            $cycle = PayrollCycle::create([
                'fecha_inicio' => $periodStart,
                'fecha_fin' => $periodEnd,
                'fecha_pago' => $periodEnd,
                'estado' => PayrollCycle::STATUS_OPEN,
            ]);
        }

        // Create some approved shifts linked to the cycle
        Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'fecha_inicio' => $periodStart . ' 08:00:00',
            'fecha_fin' => $periodStart . ' 16:00:00',
            'status' => Shift::STATUS_APPROVED,
            'total_hours' => 8,
            'total_pago' => 80000,
            'is_voided' => false,
            'payroll_cycle_id' => $cycle->id,
        ]);

        // First generation should succeed
        $response1 = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payrolls', 1);

        // Second generation for same period should fail
        $response2 = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        
        $response2->assertSessionHasErrors(['error']);
        $this->assertDatabaseCount('payrolls', 1);
    }

    public function test_cannot_pay_already_paid_payroll(): void
    {
        $this->actingAs($this->admin);

        $cycle = PayrollCycle::factory()->create();
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_cycle_id' => $cycle->id,
            'estado' => Payroll::STATUS_PAID,
        ]);

        $response = $this->patch(route('payrolls.updateStatus', $payroll), [
            'estado' => Payroll::STATUS_PAID,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(Payroll::STATUS_PAID, $payroll->fresh()->estado);
    }

    public function test_paid_payroll_is_immutable(): void
    {
        $this->actingAs($this->admin);

        $cycle = PayrollCycle::factory()->create();
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_cycle_id' => $cycle->id,
            'estado' => Payroll::STATUS_PAID,
            'total_amount' => 1000,
        ]);

        // Try to update total_amount directly (via model)
        try {
            $payroll->update(['total_amount' => 2000]);
            $this->fail('Should have thrown a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('inmutable', $e->getMessage());
        }

        $this->assertEquals(1000, $payroll->fresh()->total_amount);
    }

    public function test_total_amount_calculation_accuracy(): void
    {
        $this->actingAs($this->admin);

        $periodStart = '2026-06-01';
        $periodEnd = '2026-06-01'; // 1 day

        // Active contract for the employee
        Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $cycle = PayrollCycle::where('fecha_inicio', $periodStart)
            ->where('fecha_fin', $periodEnd)
            ->first();

        if (! $cycle) {
            $cycle = PayrollCycle::create([
                'fecha_inicio' => $periodStart,
                'fecha_fin' => $periodEnd,
                'fecha_pago' => $periodEnd,
                'estado' => PayrollCycle::STATUS_OPEN,
            ]);
        }

        // 8 hours shift
        Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'fecha_inicio' => $periodStart . ' 08:00:00',
            'fecha_fin' => $periodStart . ' 16:00:00',
            'status' => Shift::STATUS_APPROVED,
            'total_hours' => 8,
            'total_pago' => 80000, // 8 * 10,000
            'is_voided' => false,
            'payroll_cycle_id' => $cycle->id,
        ]);

        $response = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $response->assertSessionHasNoErrors();
        
        $payroll = Payroll::where('employee_id', $this->employee->id)
            ->where('payroll_cycle_id', $cycle->id)
            ->first();
        
        $this->assertNotNull($payroll, 'Payroll was not created');
        // Base proportional for 1 day: (2,400,000 / 30) = 80,000
        $this->assertEquals(80000, (float)$payroll->total_pagado);
    }

    public function test_supervisor_can_create_but_not_pay(): void
    {
        $this->actingAs($this->supervisor);

        $periodStart = '2026-07-01';
        $periodEnd = '2026-07-15';

        // Active contract for the employee
        Contract::factory()->create([
            'employee_id' => $this->employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => '2026-01-01',
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        $cycle = PayrollCycle::factory()->create([
            'fecha_inicio' => $periodStart,
            'fecha_fin' => $periodEnd,
        ]);

        Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Shift::STATUS_APPROVED,
            'payroll_cycle_id' => $cycle->id,
        ]);

        // Supervisor should be able to generate
        $response = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $response->assertSessionHasNoErrors();
        $payroll = Payroll::where('employee_id', $this->employee->id)->latest()->first();
        $this->assertNotNull($payroll);

        // Supervisor should NOT be able to pay
        $responsePay = $this->patch(route('payrolls.updateStatus', $payroll), [
            'estado' => Payroll::STATUS_PAID,
        ]);

        $responsePay->assertSessionHasErrors(['error']);
        $this->assertEquals(Payroll::STATUS_PENDING, $payroll->fresh()->estado);
    }
}
