<?php

namespace App\Actions;

use InvalidArgumentException;

class CalculateShiftPaymentsAction
{
    public const HOURS_PER_MONTH = 240;
    
    // Recargos (Tasas sobre el valor de la hora ordinaria)
    public const RATE_DIURNA = 1.0;
    public const RATE_NOCTURNA = 1.35;
    public const RATE_EXTRA_DIURNA = 1.25;
    public const RATE_EXTRA_NOCTURNA = 1.75;

    /**
     * Calculates the payment breakdown for a work shift including overtime.
     *
     * @param float $regularHours (Total regular hours: diurnas + nocturnas)
     * @param float $diurnasHours (Regular day hours)
     * @param float $nocturnasHours (Regular night hours)
     * @param float $overtimeDiurnas
     * @param float $overtimeNocturnas
     * @param float $salarioBase
     * @return array{valor_hora: float, pago_diurno: float, pago_nocturno: float, pago_extra_diurno: float, pago_extra_nocturno: float, total_pago: float}
     * @throws InvalidArgumentException
     */
    public function __invoke(
        float $regularHours,
        float $diurnasHours,
        float $nocturnasHours,
        float $overtimeDiurnas,
        float $overtimeNocturnas,
        float $salarioBase
    ): array {
        return $this->handle($regularHours, $diurnasHours, $nocturnasHours, $overtimeDiurnas, $overtimeNocturnas, $salarioBase);
    }

    /**
     * @param float $regularHours
     * @param float $diurnasHours
     * @param float $nocturnasHours
     * @param float $overtimeDiurnas
     * @param float $overtimeNocturnas
     * @param float $salarioBase
     * @return array{valor_hora: float, pago_diurno: float, pago_nocturno: float, pago_extra_diurno: float, pago_extra_nocturno: float, total_pago: float}
     * @throws InvalidArgumentException
     */
    public function handle(
        float $regularHours,
        float $diurnasHours,
        float $nocturnasHours,
        float $overtimeDiurnas,
        float $overtimeNocturnas,
        float $salarioBase
    ): array {
        // 1. Validaciones básicas
        if ($salarioBase <= 0) {
            throw new InvalidArgumentException('El salario base debe ser mayor a 0.');
        }

        if ($regularHours < 0 || $diurnasHours < 0 || $nocturnasHours < 0 || $overtimeDiurnas < 0 || $overtimeNocturnas < 0) {
            throw new InvalidArgumentException('Las horas trabajadas no pueden ser negativas.');
        }

        // 2. Validación de consistencia
        if (round($diurnasHours + $nocturnasHours, 2) !== round($regularHours, 2)) {
            throw new InvalidArgumentException('La suma de horas ordinarias (diurnas + nocturnas) no coincide con el total de horas regulares.');
        }

        // 3. Cálculo técnico con precisión (valor crudo)
        $valorHoraRaw = $salarioBase / self::HOURS_PER_MONTH;
        
        $pagoDiurno = $diurnasHours * $valorHoraRaw * self::RATE_DIURNA;
        $pagoNocturno = $nocturnasHours * $valorHoraRaw * self::RATE_NOCTURNA;
        $pagoExtraDiurno = $overtimeDiurnas * $valorHoraRaw * self::RATE_EXTRA_DIURNA;
        $pagoExtraNocturno = $overtimeNocturnas * $valorHoraRaw * self::RATE_EXTRA_NOCTURNA;
        
        $totalPago = $pagoDiurno + $pagoNocturno + $pagoExtraDiurno + $pagoExtraNocturno;

        // 4. Redondeo final
        return [
            'valor_hora' => round($valorHoraRaw, 2),
            'pago_diurno' => round($pagoDiurno, 2),
            'pago_nocturno' => round($pagoNocturno, 2),
            'pago_extra_diurno' => round($pagoExtraDiurno, 2),
            'pago_extra_nocturno' => round($pagoExtraNocturno, 2),
            'total_pago' => round($totalPago, 2),
        ];
    }
}
