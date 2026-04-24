<?php

namespace App\Actions;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class CalculateOvertimeAction
{
    public const MAX_REGULAR_HOURS_PER_DAY = 8.0;

    /**
     * Calculates the breakdown between regular and overtime hours, resetting the limit every day.
     *
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{regular_hours: float, overtime_hours: float}
     * @throws InvalidArgumentException
     */
    public function __invoke(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        return $this->handle($fechaInicio, $fechaFin);
    }

    /**
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{regular_hours: float, overtime_hours: float}
     * @throws InvalidArgumentException
     */
    public function handle(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        if ($fechaFin->lessThanOrEqualTo($fechaInicio)) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        $totalRegular = 0.0;
        $totalOvertime = 0.0;

        // Iteramos por cada día calendario que abarca el turno
        $currentDate = $fechaInicio->copy()->startOfDay();
        $endLimit = $fechaFin->copy()->endOfDay();

        while ($currentDate->lessThanOrEqualTo($endLimit)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            // Determinamos la intersección del turno con el día actual
            $intersectStart = $fechaInicio->greaterThan($dayStart) ? $fechaInicio : $dayStart;
            $intersectEnd = $fechaFin->lessThan($dayEnd) ? $fechaFin : $dayEnd;

            if ($intersectStart->lessThan($intersectEnd)) {
                $minutesInDay = $intersectStart->diffInMinutes($intersectEnd, false);
                $hoursInDay = $minutesInDay / 60;
                
                $dayRegular = min($hoursInDay, self::MAX_REGULAR_HOURS_PER_DAY);
                $dayOvertime = max(0.0, $hoursInDay - self::MAX_REGULAR_HOURS_PER_DAY);
                
                $totalRegular += $dayRegular;
                $totalOvertime += $dayOvertime;
            }

            $currentDate->addDay();
        }

        return [
            'regular_hours' => round($totalRegular, 2),
            'overtime_hours' => round($totalOvertime, 2),
        ];
    }
}
