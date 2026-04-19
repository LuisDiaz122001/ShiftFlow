<?php

namespace Tests\Unit\Actions;

use App\Actions\CalculateLawDeductionsAction;
use App\Models\LaborRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateLawDeductionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_4_percent_deductions_correctly(): void
    {
        $rule = LaborRule::create([
            'vigente_desde' => '2026-01-01',
            'porcentaje_salud' => 4.00,
            'porcentaje_pension' => 4.00,
            'horas_max_diarias' => 8,
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
            'extra_diurna' => 25,
            'extra_nocturna' => 75,
            'recargo_nocturno' => 35,
            'recargo_dominical' => 75,
        ]);

        $ibc = 1000000.00;
        $action = new CalculateLawDeductionsAction();
        $result = $action->execute($ibc, $rule);

        $this->assertEquals(40000.00, $result['salud']);
        $this->assertEquals(40000.00, $result['pension']);
        $this->assertEquals(80000.00, $result['total_deducciones']);
    }

    public function test_it_handles_custom_percentages(): void
    {
        $rule = LaborRule::create([
            'vigente_desde' => '2026-01-01',
            'porcentaje_salud' => 5.00,
            'porcentaje_pension' => 0.00,
            'horas_max_diarias' => 8,
            'hora_diurna_inicio' => '06:00:00',
            'hora_nocturna_inicio' => '21:00:00',
            'extra_diurna' => 25,
            'extra_nocturna' => 75,
            'recargo_nocturno' => 35,
            'recargo_dominical' => 75,
        ]);

        $ibc = 1000000.00;
        $action = new CalculateLawDeductionsAction();
        $result = $action->execute($ibc, $rule);

        $this->assertEquals(50000.00, $result['salud']);
        $this->assertEquals(0.00, $result['pension']);
    }
}
