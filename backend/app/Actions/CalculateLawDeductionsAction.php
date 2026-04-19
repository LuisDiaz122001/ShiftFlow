<?php

namespace App\Actions;

use App\Models\LaborRule;

class CalculateLawDeductionsAction
{
    /**
     * Calcula las deducciones de ley (Salud y Pensión) basadas en el IBC.
     *
     * @param float $ibc Ingreso Base de Cotización (Salario + Recargos)
     * @param LaborRule $rule Regla laboral con los porcentajes vigentes
     * @return array{
     *     salud: float,
     *     pension: float,
     *     total_deducciones: float
     * }
     */
    public function execute(float $ibc, LaborRule $rule): array
    {
        $porcentajeSalud = (float) $rule->porcentaje_salud / 100;
        $porcentajePension = (float) $rule->porcentaje_pension / 100;

        $deduccionSalud = round($ibc * $porcentajeSalud, 2);
        $deduccionPension = round($ibc * $porcentajePension, 2);

        return [
            'salud' => $deduccionSalud,
            'pension' => $deduccionPension,
            'total_deducciones' => round($deduccionSalud + $deduccionPension, 2),
        ];
    }
}
