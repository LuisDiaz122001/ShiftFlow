<?php

namespace App\Actions;

use App\Models\Shift;
use App\Services\PayrollCalculatorService;

class CalculateShiftAction
{
    public function __construct(
        private readonly PayrollCalculatorService $calculator,
        private readonly AssignShiftToCycleAction $assignCycle,
    ) {
    }

    /**
     * Orquestador para calcular un turno y persistir el resultado.
     *
     * @param Shift $shift
     * @return void
     */
    public function execute(Shift $shift): void
    {
        // 1. Asegurar asociación a un ciclo
        $this->assignCycle->execute($shift);
        
        $shift->loadMissing('payrollCycle');

        // Ajuste 3 Final: Bloquear recálculo automático si el ciclo ya fue generado o cerrado
        if ($shift->payrollCycle->isLockedForCalculation()) {
            throw new \RuntimeException('No se puede recalcular un turno en un ciclo liquidado o cerrado.');
        }

        // 2. Ejecutar cálculo técnico
        $result = $this->calculator->calculate($shift);

        // 3. Persistir resultado directamente en el modelo Shift (Fuente Única de Verdad)
        $shift->update([
            'diurnas_hours' => $result['horas_diurnas'] + $result['horas_extra_diurnas'],
            'nocturnas_hours' => $result['horas_nocturnas'] + $result['horas_extra_nocturnas'],
            'total_hours' => $result['horas_diurnas'] + $result['horas_nocturnas'] + $result['horas_extra_diurnas'] + $result['horas_extra_nocturnas'],
            'total_pago' => $result['total'],
        ]);
    }
}
