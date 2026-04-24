<?php

namespace App\Actions;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class CalculateShiftHoursAction
{
    /**
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return float
     */
    public function __invoke(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): float
    {
        return $this->handle($fechaInicio, $fechaFin);
    }

    /**
     * Calculates the total hours worked between two dates in UTC.
     *
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return float
     * @throws InvalidArgumentException
     */
    public function handle(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): float
    {
        // Normalización a UTC para evitar problemas con cambios de zona horaria
        $start = $fechaInicio->copy()->utc();
        $end = $fechaFin->copy()->utc();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        // diffInMinutes con comportamiento no-absoluto (false)
        $minutes = $start->diffInMinutes($end, false);

        return round($minutes / 60, 2);
    }
}
