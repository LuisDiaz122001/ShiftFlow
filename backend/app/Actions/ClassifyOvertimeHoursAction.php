<?php

namespace App\Actions;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class ClassifyOvertimeHoursAction
{
    public const MAX_REGULAR_HOURS_PER_DAY = 8.0;

    /**
     * Calculates regular hours and classifies overtime hours into diurnas and nocturnas.
     *
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{regular_hours: float, overtime_hours: float, overtime_diurnas: float, overtime_nocturnas: float}
     * @throws InvalidArgumentException
     */
    public function __invoke(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        return $this->handle($fechaInicio, $fechaFin);
    }

    /**
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{regular_hours: float, overtime_hours: float, overtime_diurnas: float, overtime_nocturnas: float}
     * @throws InvalidArgumentException
     */
    public function handle(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        // Normalización a UTC
        $start = $fechaInicio->copy()->utc();
        $end = $fechaFin->copy()->utc();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        $totalRegularMinutes = 0.0;
        $totalOvertimeDiurnasMinutes = 0.0;
        $totalOvertimeNocturnasMinutes = 0.0;

        $currentDate = $start->copy()->startOfDay();
        $limitDate = $end->copy()->endOfDay();

        while ($currentDate->lessThanOrEqualTo($limitDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            // Intersección del turno con el día calendario actual
            $intersectStart = $start->greaterThan($dayStart) ? $start->copy() : $dayStart->copy();
            $intersectEnd = $end->lessThan($dayEnd) ? $end->copy() : $dayEnd->copy();

            if ($intersectStart->lessThan($intersectEnd)) {
                $this->processDay(
                    $intersectStart, 
                    $intersectEnd, 
                    $totalRegularMinutes, 
                    $totalOvertimeDiurnasMinutes, 
                    $totalOvertimeNocturnasMinutes
                );
            }

            $currentDate->addDay();
        }

        $overtimeTotalMinutes = $totalOvertimeDiurnasMinutes + $totalOvertimeNocturnasMinutes;

        return [
            'regular_hours' => round($totalRegularMinutes / 60, 2),
            'overtime_hours' => round($overtimeTotalMinutes / 60, 2),
            'overtime_diurnas' => round($totalOvertimeDiurnasMinutes / 60, 2),
            'overtime_nocturnas' => round($totalOvertimeNocturnasMinutes / 60, 2),
        ];
    }

    /**
     * Processes a single calendar day segment.
     */
    private function processDay(
        CarbonInterface $start, 
        CarbonInterface $end, 
        &$totalRegular, 
        &$totalOvertimeDiurnas, 
        &$totalOvertimeNocturnas
    ): void {
        // Puntos de transición para el día
        $points = [$start->copy(), $end->copy()];
        $sixAm = $start->copy()->setHour(6)->setMinute(0)->setSecond(0);
        $ninePm = $start->copy()->setHour(21)->setMinute(0)->setSecond(0);

        if ($sixAm->greaterThan($start) && $sixAm->lessThan($end)) $points[] = $sixAm;
        if ($ninePm->greaterThan($start) && $ninePm->lessThan($end)) $points[] = $ninePm;

        usort($points, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        $accumulatedDayMinutes = 0;
        $limitMinutes = self::MAX_REGULAR_HOURS_PER_DAY * 60;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $duration = $p1->diffInMinutes($p2, false);
            
            if ($duration <= 0) continue;

            $startMinutes = $accumulatedDayMinutes;
            $accumulatedDayMinutes += $duration;

            if ($startMinutes >= $limitMinutes) {
                // Todo el segmento es tiempo extra
                $this->classify($p1->hour, $duration, $totalOvertimeDiurnas, $totalOvertimeNocturnas);
            } elseif ($accumulatedDayMinutes > $limitMinutes) {
                // Parte ordinaria, parte extra
                $regularPart = $limitMinutes - $startMinutes;
                $overtimePart = $accumulatedDayMinutes - $limitMinutes;

                $totalRegular += $regularPart;
                $this->classify($p1->hour, $overtimePart, $totalOvertimeDiurnas, $totalOvertimeNocturnas);
            } else {
                // Todo ordinario
                $totalRegular += $duration;
            }
        }
    }

    /**
     * Classifies minutes into diurnas or nocturnas.
     */
    private function classify(int $hour, float $minutes, &$diurnas, &$nocturnas): void
    {
        if ($hour >= 6 && $hour < 21) {
            $diurnas += $minutes;
        } else {
            $nocturnas += $minutes;
        }
    }
}
