<?php

namespace App\Actions;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class ClassifyShiftHoursAction
{
    /**
     * Classifies worked hours into diurnas (06:00-21:00) and nocturnas (21:00-06:00).
     *
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{total: float, diurnas: float, nocturnas: float}
     */
    public function __invoke(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        return $this->handle($fechaInicio, $fechaFin);
    }

    /**
     * @param CarbonInterface $fechaInicio
     * @param CarbonInterface $fechaFin
     * @return array{total: float, diurnas: float, nocturnas: float}
     * @throws InvalidArgumentException
     */
    public function handle(CarbonInterface $fechaInicio, CarbonInterface $fechaFin): array
    {
        // Normalización a UTC antes de cualquier cálculo
        $start = $fechaInicio->copy()->utc();
        $end = $fechaFin->copy()->utc();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        $diurnasMinutes = 0;
        $nocturnasMinutes = 0;

        // Identificamos los puntos de transición (06:00 y 21:00)
        $points = [$start->copy(), $end->copy()];
        
        $currentDate = $start->copy()->startOfDay();
        $limitDate = $end->copy()->endOfDay();

        while ($currentDate->lessThanOrEqualTo($limitDate)) {
            // Generamos los puntos de cambio de jornada para cada día en el rango
            $sixAm = $currentDate->copy()->setHour(6)->setMinute(0)->setSecond(0);
            $ninePm = $currentDate->copy()->setHour(21)->setMinute(0)->setSecond(0);

            if ($sixAm->greaterThan($start) && $sixAm->lessThan($end)) {
                $points[] = $sixAm;
            }
            if ($ninePm->greaterThan($start) && $ninePm->lessThan($end)) {
                $points[] = $ninePm;
            }

            $currentDate->addDay();
        }

        // Ordenamos y eliminamos duplicados (usando timestamp para comparación)
        usort($points, fn ($a, $b) => $a->timestamp <=> $b->timestamp);
        
        // Procesamos los segmentos
        for ($i = 0; $i < count($points) - 1; $i++) {
            $p1 = $points[$i];
            $p2 = $points[$i + 1];

            // diffInMinutes con comportamiento no-absoluto (false)
            $duration = $p1->diffInMinutes($p2, false);
            if ($duration <= 0) continue;

            // Usamos el inicio del segmento (p1) para clasificar la jornada
            // Dado que hemos segmentado en los puntos de transición, el segmento es puro
            $hour = $p1->hour;

            if ($hour >= 6 && $hour < 21) {
                $diurnasMinutes += $duration;
            } else {
                $nocturnasMinutes += $duration;
            }
        }

        $diurnas = round($diurnasMinutes / 60, 2);
        $nocturnas = round($nocturnasMinutes / 60, 2);
        $total = round(($diurnasMinutes + $nocturnasMinutes) / 60, 2);

        return [
            'total' => $total,
            'diurnas' => $diurnas,
            'nocturnas' => $nocturnas,
        ];
    }
}
