<?php

namespace App\Actions;

use App\Models\PayrollCycle;
use App\Models\Shift;
use Carbon\CarbonInterface;
use RuntimeException;

class AssignShiftToCycleAction
{
    /**
     * Busca el ciclo de nómina abierto para las fechas del turno y lo asigna.
     * Implementación MANUAL: Arroja error si no existe el ciclo.
     *
     * @param Shift $shift
     * @return Shift
     * @throws RuntimeException
     */
    public function execute(Shift $shift): Shift
    {
        /** @var CarbonInterface $start */
        $start = $shift->fecha_inicio;
        $dateString = $start->toDateString();

        $cycle = PayrollCycle::query()
            ->where('estado', '!=', PayrollCycle::STATUS_CLOSED)
            ->whereDate('fecha_inicio', '<=', $dateString)
            ->whereDate('fecha_fin', '>=', $dateString)
            ->first();

        if (! $cycle) {
            throw new RuntimeException("No existe un ciclo de nómina abierto para la fecha {$dateString}. El administrador debe crear el ciclo primero.");
        }

        $shift->payroll_cycle_id = $cycle->id;
        $shift->save();

        return $shift;
    }
}
