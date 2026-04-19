<?php

namespace App\Actions;

use App\Models\Shift;
use App\Models\ShiftCalculation;

class UpsertShiftCalculationAction
{
    /**
     * Guarda o actualiza el resultado del cálculo de un turno de forma idempotente.
     *
     * @param Shift $shift
     * @param array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<int, array<string, mixed>>
     * } $calculationResults
     * @return ShiftCalculation
     */
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
