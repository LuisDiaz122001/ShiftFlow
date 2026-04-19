<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainIntegrityGoldTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employeeUser;
    protected Employee $employee;
    protected \App\Models\PayrollCycle $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->employeeUser = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $this->employee = Employee::factory()->create(['user_id' => $this->employeeUser->id]);

        // Crear ciclo para las pruebas
        $this->cycle = \App\Models\PayrollCycle::create([
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-30',
            'fecha_pago' => '2026-04-30',
            'estado' => \App\Models\PayrollCycle::STATUS_OPEN
        ]);

        // Crear regla laboral
        \App\Models\LaborRule::create([
            'vigente_desde' => '2026-01-01',
            'horas_max_diarias' => 8,
            'recargo_nocturno' => 0.35,
            'recargo_dominical' => 0.75,
            'extra_diurna' => 0.25,
            'extra_nocturna' => 0.75,
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
        ]);

        // Crear contrato para el empleado
        \App\Models\Contract::create([
            'employee_id' => $this->employee->id,
            'salario_base' => 1300000,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'estado' => 'activo'
        ]);
    }

    /** @test */
    public function test_zero_delete_policy_enforcement()
    {
        $shift = Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->admin->id,
            'status' => Shift::STATUS_PENDING
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La eliminación física de turnos está prohibida');

        $shift->delete();
    }

    /** @test */
    public function test_immutability_of_approved_shifts()
    {
        $shift = Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Shift::STATUS_APPROVED
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Un turno aprobado/rechazado es inmutable');

        $shift->update(['fecha_inicio' => now()->addDay()]);
    }

    /** @test */
    public function test_employee_cannot_spoof_employee_id()
    {
        $otherEmployee = Employee::factory()->create();

        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/v1/shifts', [
                'employee_id' => $otherEmployee->id, // Intento de spoofing
                'fecha_inicio' => '2026-04-20 08:00:00',
                'fecha_fin' => '2026-04-20 16:00:00',
            ]);

        $response->assertStatus(201);
        
        // Verificamos que se inyectó el ID del empleado autenticado, no el enviado
        $this->assertEquals($this->employee->id, $response->json('data.employee_id'));
        $this->assertEquals(Shift::STATUS_PENDING, $response->json('data.status'));
    }

    /** @test */
    public function test_admin_can_approve_shift()
    {
        $shift = Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Shift::STATUS_PENDING
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/shifts/{$shift->id}/approve");

        $response->assertStatus(200);
        $this->assertEquals(Shift::STATUS_APPROVED, $response->json('data.status'));
        $this->assertEquals($this->admin->id, $response->json('data.approved_by'));
    }

    /** @test */
    public function test_voiding_process_and_immutability()
    {
        $shift = Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Shift::STATUS_APPROVED
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/shifts/{$shift->id}/void");

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_voided'));

        // Intentar anular de nuevo
        $responseRepeat = $this->actingAs($this->admin)
            ->postJson("/api/v1/shifts/{$shift->id}/void");
        
        $responseRepeat->assertStatus(422);
        $responseRepeat->assertJsonFragment(['Este turno ya ha sido anulado.']);
    }

    /** @test */
    public function test_unique_replacement_enforcement()
    {
        $shift = Shift::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Shift::STATUS_APPROVED,
            'is_voided' => true,
            'voided_at' => now(),
            'voided_by' => $this->admin->id
        ]);

        // Primer reemplazo (Éxito)
        $this->actingAs($this->admin)->postJson('/api/v1/shifts', [
            'employee_id' => $this->employee->id,
            'fecha_inicio' => '2026-04-20 08:00:00',
            'fecha_fin' => '2026-04-20 16:00:00',
            'voids_shift_id' => $shift->id
        ])->assertStatus(201);

        // Segundo reemplazo del mismo turno (Error por índice único)
        $duplicate = $this->actingAs($this->admin)->postJson('/api/v1/shifts', [
            'employee_id' => $this->employee->id,
            'fecha_inicio' => '2026-04-21 08:00:00',
            'fecha_fin' => '2026-04-21 16:00:00',
            'voids_shift_id' => $shift->id
        ]);

        // Laravel/SQLite lanzará una excepción de integridad que se convierte en 500 o similar si no se maneja,
        // o podemos validar que falle la creación.
        $duplicate->assertStatus(500); 
    }
}
