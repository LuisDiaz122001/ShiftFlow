<?php

namespace App\Actions;

use App\Models\Shift;
use App\Models\ShiftCalculation;

class UpsertShiftCalculationAction
{
    public function execute(Shift $shift, array $calculationResults): ShiftCalculation
    {
        return ShiftCalculation::updateOrCreate(
            ['shift_id' => $shift->id],
            [
                'horas_diurnas' => $calculationResults['horas_diurnas'],
                'horas_nocturnas' => $calculationResults['horas_nocturnas'],
                'horas_extra_diurnas' => $calculationResults['horas_extra_diurnas'],
                'horas_extra_nocturnas' => $calculationResults['horas_extra_nocturnas'],
                'valor_total' => $calculationResults['total'],
                'detalle_json' => $calculationResults['detalle'],
            ]
        );
    }
}
