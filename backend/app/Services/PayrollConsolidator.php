<?php

namespace App\Services;

use App\Models\PayrollDetail;
use Illuminate\Support\Collection;

class PayrollConsolidator
{
    /**
     * Consolida de forma determinística los desgloses de múltiples turnos en conceptos contables.
     *
     * @param Collection $shiftCalculations Colección de modelos ShiftCalculation
     * @return Collection Colección de arrays listos para insertarse en PayrollDetail
     */
    public function consolidate(Collection $shiftCalculations): Collection
    {
        $items = collect();

        foreach ($shiftCalculations as $calc) {
            $breakdown = $calc->detalle_json;

            foreach ($breakdown as $segment) {
                // 1. Concepto Base: Sueldo
                $this->accumulate($items, [
                    'concept' => 'salary_base',
                    'label' => 'Sueldo base',
                    'type' => PayrollDetail::TYPE_EARNING,
                    'hours' => (float) $segment['horas'],
                    'amount' => (float) $segment['valor_base'],
                ]);

                // 2. Conceptos de Recargos
                $recargos = $segment['recargos_aplicados'] ?? [];
                foreach ($recargos as $conceptKey => $percentage) {
                    $itemKey = $this->mapKey($conceptKey);
                    $label = $this->mapLabel($conceptKey);
                    
                    // El valor del recargo es: horas * rate * (porcentaje / 100)
                    $hourlyRate = (float) $segment['valor_hora_base'];
                    $hours = (float) $segment['horas'];
                    $amount = round($hours * $hourlyRate * ($percentage / 100), 2);

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

        return $items->values();
    }

    private function accumulate(Collection $items, array $data): void
    {
        $existing = $items->firstWhere('concept', $data['concept']);

        if ($existing) {
            $index = $items->search($existing);
            $existing['hours'] += $data['hours'];
            $existing['amount'] += $data['amount'];
            $items->put($index, $existing);
        } else {
            $items->push($data);
        }
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
