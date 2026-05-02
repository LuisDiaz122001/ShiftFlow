<?php

namespace App\Actions;

use App\Models\Shift;
use App\Services\PayrollCalculatorService;

class CalculateShiftAction
{
    public function __construct(
        private readonly PayrollCalculatorService $calculator,
        private readonly AssignShiftToCycleAction $assignCycle,
        private readonly UpsertShiftCalculationAction $upsertShiftCalculation,
    ) {
    }

    public function execute(Shift $shift): void
    {
        $this->assignCycle->execute($shift);

        $shift->loadMissing('payrollCycle');

        if ($shift->payrollCycle->isLockedForCalculation()) {
            throw new \RuntimeException('No se puede recalcular un turno en un ciclo liquidado o cerrado.');
        }

        $result = $this->calculator->calculate($shift);

        $this->upsertShiftCalculation->execute($shift, $result);

        $shift->update([
            'diurnas_hours' => $result['horas_diurnas'] + $result['horas_extra_diurnas'],
            'nocturnas_hours' => $result['horas_nocturnas'] + $result['horas_extra_nocturnas'],
            'total_hours' => $result['horas_diurnas'] + $result['horas_nocturnas'] + $result['horas_extra_diurnas'] + $result['horas_extra_nocturnas'],
            'total_pago' => $result['total'],
        ]);
    }
}
