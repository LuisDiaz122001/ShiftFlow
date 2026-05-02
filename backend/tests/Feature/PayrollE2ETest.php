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

class PayrollE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
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

        // Crear un contrato para el empleado
        $this->employee->contracts()->create([
            'salario_base' => 2400000, 
            'fecha_inicio' => '2020-01-01',
            'estado' => Contract::ESTADO_ACTIVO
        ]);
    }

    private function setupApprovedShiftsForPeriod(string $start, string $end): PayrollCycle
    {
        $cycle = PayrollCycle::firstOrCreate([
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
        ], [
            'fecha_pago' => $end,
            'estado' => PayrollCycle::STATUS_OPEN
        ]);

        Shift::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'fecha_inicio' => Carbon::parse($start)->addDay()->setHour(8)->toDateTimeString(),
            'fecha_fin' => Carbon::parse($start)->addDay()->setHour(17)->toDateTimeString(),
            'status' => Shift::STATUS_APPROVED,
            'payroll_cycle_id' => $cycle->id,
        ]);

        return $cycle;
    }

    public function test_full_payroll_flow()
    {
        $this->actingAs($this->admin);

        $start = '2026-05-01';
        $end = '2026-05-15';

        $this->setupApprovedShiftsForPeriod($start, $end);

        // 1. Generar nómina
        $response = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $start,
            'period_end' => $end,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'estado' => Payroll::STATUS_PENDING
        ]);

        $payroll = Payroll::first();

        // 2. Pagar nómina
        $response = $this->patch(route('payrolls.updateStatus', $payroll), [
            'estado' => Payroll::STATUS_PAID,
        ]);
        $response->assertRedirect();
        $this->assertEquals(Payroll::STATUS_PAID, $payroll->fresh()->estado);

        // 3. Verificar auditoría
        $this->assertDatabaseHas('payroll_logs', [
            'payroll_id' => $payroll->id,
            'action' => 'create'
        ]);
        $this->assertDatabaseHas('payroll_logs', [
            'payroll_id' => $payroll->id,
            'action' => 'pay'
        ]);
    }

    public function test_cannot_duplicate_payroll()
    {
        $this->actingAs($this->admin);

        $start = '2026-05-01';
        $end = '2026-05-15';

        $this->setupApprovedShiftsForPeriod($start, $end);

        // Primera generación
        $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $start,
            'period_end' => $end,
        ]);
        $this->assertEquals(1, Payroll::count());

        // Segunda generación (debe fallar)
        $response = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $start,
            'period_end' => $end,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(1, Payroll::count());
    }

    public function test_cannot_generate_in_closed_period()
    {
        $this->actingAs($this->admin);

        $start = '2026-04-01';
        $end = '2026-04-30';

        $cycle = $this->setupApprovedShiftsForPeriod($start, $end);
        $cycle->update(['estado' => PayrollCycle::STATUS_CLOSED]);

        $response = $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $start,
            'period_end' => $end,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(0, Payroll::count());
    }

    public function test_supervisor_cannot_pay_payroll()
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
        $this->actingAs($supervisor);

        $start = '2026-06-01';
        $end = '2026-06-15';
        $this->setupApprovedShiftsForPeriod($start, $end);

        // Supervisor can create
        $this->post(route('payrolls.store'), [
            'employee_id' => $this->employee->id,
            'period_start' => $start,
            'period_end' => $end,
        ]);
        
        $payroll = Payroll::first();
        $this->assertNotNull($payroll);

        // Supervisor CANNOT pay
        $response = $this->patch(route('payrolls.updateStatus', $payroll), [
            'estado' => Payroll::STATUS_PAID,
        ]);

        $response->assertStatus(403); // Or assertRedirect with error if handled by controller, but updateStatus has validation and logic
        $this->assertEquals(Payroll::STATUS_PENDING, $payroll->fresh()->estado);
    }
}
