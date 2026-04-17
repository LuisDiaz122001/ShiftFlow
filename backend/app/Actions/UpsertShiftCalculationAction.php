<?php

namespace App\Actions;

use App\Models\Shift;
use App\Models\ShiftCalculation;

class UpsertShiftCalculationAction
{
    /**
     * @param array{
     *     total: float|int|string,
     *     horas_diurnas: float|int|string,
     *     horas_nocturnas: float|int|string,
     *     horas_extra_diurnas: float|int|string,
     *     horas_extra_nocturnas: float|int|string,
     *     detalle: array<int, array<string, mixed>>
     * } $calculation
     */
    public function handle(Shift $shift, array $calculation): ShiftCalculation
    {
        return ShiftCalculation::query()->updateOrCreate(
            ['shift_id' => $shift->id],
            [
                'horas_diurnas' => round((float) $calculation['horas_diurnas'], 2),
                'horas_nocturnas' => round((float) $calculation['horas_nocturnas'], 2),
                'horas_extra_diurnas' => round((float) $calculation['horas_extra_diurnas'], 2),
                'horas_extra_nocturnas' => round((float) $calculation['horas_extra_nocturnas'], 2),
                'valor_total' => round((float) $calculation['total'], 2),
                'detalle_json' => $calculation['detalle'],
            ]
        );
    }
}
