<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use App\Models\LaborRule;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class PayrollOperationalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->employee = Employee::factory()->create();
        
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

        $this->employee->contracts()->create([
            'salario_base' => 2400000, 
            'fecha_inicio' => '2020-01-01',
            'estado' => Contract::ESTADO_ACTIVO
        ]);
    }

    public function test_bulk_generation_processes_multiple_employees()
    {
        $this->actingAs($this->admin);

        $start = '2026-05-01';
        $end = '2026-05-15';

        // Create second employee
        $employee2 = Employee::factory()->create();
        $employee2->contracts()->create([
            'salario_base' => 2000000, 
            'fecha_inicio' => '2020-01-01',
            'estado' => Contract::ESTADO_ACTIVO
        ]);

        // Create shifts for both
        $cycle = PayrollCycle::create([
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
            'fecha_pago' => $end,
            'estado' => PayrollCycle::STATUS_OPEN
        ]);

        Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'fecha_inicio' => "{$start} 08:00:00",
            'fecha_fin' => "{$start} 17:00:00",
            'status' => Shift::STATUS_APPROVED,
            'payroll_cycle_id' => $cycle->id,
        ]);

        Shift::factory()->create([
            'employee_id' => $employee2->id,
            'fecha_inicio' => "{$start} 08:00:00",
            'fecha_fin' => "{$start} 17:00:00",
            'status' => Shift::STATUS_APPROVED,
            'payroll_cycle_id' => $cycle->id,
        ]);

        $response = $this->post(route('payrolls.bulkStore'), [
            'period_start' => $start,
            'period_end' => $end,
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, Payroll::count());
    }

    public function test_bulk_payment_processes_multiple_ids()
    {
        $this->actingAs($this->admin);

        $cycle = PayrollCycle::factory()->create();
        $p1 = Payroll::factory()->create(['estado' => Payroll::STATUS_PENDING, 'payroll_cycle_id' => $cycle->id]);
        $p2 = Payroll::factory()->create(['estado' => Payroll::STATUS_PENDING, 'payroll_cycle_id' => $cycle->id]);

        $response = $this->post(route('payrolls.bulkPay'), [
            'payroll_ids' => [$p1->id, $p2->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals(Payroll::STATUS_PAID, $p1->fresh()->estado);
        $this->assertEquals(Payroll::STATUS_PAID, $p2->fresh()->estado);
    }
}
