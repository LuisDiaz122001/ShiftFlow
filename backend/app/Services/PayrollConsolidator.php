<?php

namespace App\Services;

use App\Models\PayrollDetail;
use App\Models\Shift;
use App\Models\ShiftCalculation;
use Illuminate\Support\Collection;

class PayrollConsolidator
{
    /**
     * @param Collection<int, Shift> $shifts
     * @return Collection<int, array<string, mixed>>
     */
    public function consolidate(Collection $shifts, float $hourlyRate): Collection
    {
        $items = collect();

        foreach ($shifts as $shift) {
            $calculation = $shift->calculation;

            if ($calculation instanceof ShiftCalculation && is_array($calculation->detalle_json)) {
                $this->consolidateFromCalculation($items, $calculation);
                continue;
            }

            $this->consolidateFromShift($items, $shift, $hourlyRate);
        }

        return $items->values();
    }

    private function consolidateFromCalculation(Collection $items, ShiftCalculation $calculation): void
    {
        foreach ($calculation->detalle_json as $segment) {
            $hours = (float) ($segment['horas'] ?? 0);
            $baseAmount = (float) ($segment['valor_base'] ?? 0);

            $this->accumulate($items, [
                'concept' => 'salary_base',
                'label' => 'Sueldo base',
                'type' => PayrollDetail::TYPE_EARNING,
                'hours' => $hours,
                'amount' => $baseAmount,
            ]);

            foreach (($segment['recargos_aplicados'] ?? []) as $conceptKey => $percentage) {
                $itemKey = $this->mapKey($conceptKey);
                $label = $this->mapLabel($conceptKey);
                $hourlyRate = (float) ($segment['valor_hora_base'] ?? 0);
                $amount = round($hours * $hourlyRate * (((float) $percentage) / 100), 2);

                $this->accumulate($items, [
                    'concept' => $itemKey,
                    'label' => $label,
                    'type' => PayrollDetail::TYPE_EARNING,
                    'hours' => $hours,
                    'amount' => $amount,
                ]);
            }
        }
    }

    private function consolidateFromShift(Collection $items, Shift $shift, float $hourlyRate): void
    {
        $hours = round((float) $shift->total_hours, 2);
        $baseAmount = round($hours * $hourlyRate, 2);
        $premiumAmount = round((float) $shift->total_pago - $baseAmount, 2);

        $this->accumulate($items, [
            'concept' => 'salary_base',
            'label' => 'Sueldo base',
            'type' => PayrollDetail::TYPE_EARNING,
            'hours' => $hours,
            'amount' => $baseAmount,
        ]);

        if ($premiumAmount > 0) {
            $this->accumulate($items, [
                'concept' => 'shift_premium',
                'label' => 'Recargos de turno',
                'type' => PayrollDetail::TYPE_EARNING,
                'hours' => $hours,
                'amount' => $premiumAmount,
            ]);
        }
    }

    private function accumulate(Collection $items, array $data): void
    {
        $existing = $items->firstWhere('concept', $data['concept']);

        if ($existing) {
            $index = $items->search($existing);
            $existing['hours'] += $data['hours'];
            $existing['amount'] += $data['amount'];
            $items->put($index, $existing);

            return;
        }

        $items->push($data);
    }

    private function mapKey(string $key): string
    {
        return match ($key) {
            'recargo_nocturno' => 'night_premium',
            'recargo_dominical', 'recargo_festivo' => 'sunday_holiday_premium',
            'extra_diurna' => 'extra_day',
            'extra_nocturna' => 'extra_night',
            default => $key,
        };
    }

    private function mapLabel(string $key): string
    {
        return match ($key) {
            'recargo_nocturno' => 'Recargo nocturno',
            'recargo_dominical' => 'Recargo dominical',
            'recargo_festivo' => 'Recargo festivo',
            'extra_diurna' => 'Hora extra diurna',
            'extra_nocturna' => 'Hora extra nocturna',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }
}
