<?php

namespace Tests\Feature\Actions;

use App\Actions\CalculateShiftAction;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\Shift;
use App\Models\ShiftCalculation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateShiftFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear una regla laboral base para Colombia
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
        ]);
    }

    public function test_it_calculates_and_persists_a_shift_correctly(): void
    {
        $user = \App\Models\User::factory()->create();
        $employee = Employee::create(['nombre' => 'Juan Perez']);
        
        // Contrato de 2.4M (10.000 por hora segun referencia de 240h/mes)
        Contract::create([
            'employee_id' => $employee->id,
            'salario_base' => 2400000,
            'fecha_inicio' => Carbon::parse('2026-01-01'),
            'estado' => Contract::ESTADO_ACTIVO,
        ]);

        // Turno de 8 horas (Diurno, lunes)
        $shift = Shift::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'fecha_inicio' => Carbon::parse('2026-04-20 08:00:00'), // Lunes
            'fecha_fin' => Carbon::parse('2026-04-20 16:00:00'),
        ]);

        /** @var CalculateShiftAction $action */
        $action = app(CalculateShiftAction::class);
        $calculation = $action->execute($shift);

        // Verificaciones en Base de Datos
        $this->assertDatabaseHas('shift_calculations', [
            'shift_id' => $shift->id,
            'horas_diurnas' => 8.00,
            'valor_total' => 80000.00, // 8h * 10.000
        ]);

        // Verificaciones en el Objeto Retornado
        $this->assertInstanceOf(ShiftCalculation::class, $calculation);
        $this->assertEquals(8.00, $calculation->total_hours);
        $this->assertEquals(80000.00, $calculation->total_pay);
        $this->assertNotEmpty($calculation->breakdown);
    }
    
    public function test_it_is_idempotent_and_updates_if_exists(): void
    {
        $user = \App\Models\User::factory()->create();
        $employee = Employee::create(['nombre' => 'Juan Perez']);
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

        $action = app(CalculateShiftAction::class);
        
        // Ejecución 1
        $action->execute($shift);
        
        // Ejecución 2 (Repetida)
        $action->execute($shift);

        // Solo debe existir un registro
        $this->assertEquals(1, ShiftCalculation::where('shift_id', $shift->id)->count());
    }
}
