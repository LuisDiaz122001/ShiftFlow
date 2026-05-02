<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\LaborRule;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\PayrollDetail;
use App\Models\Shift;
use App\Services\PayrollConsolidator;
use Illuminate\Support\Facades\DB;

class GeneratePayrollAction
{
    public function __construct(
        private readonly CalculateLawDeductionsAction $calculateDeductions,
        private readonly PayrollConsolidator $consolidator,
    ) {
    }

    public function execute(Employee $employee, PayrollCycle $cycle, bool $force = false): ?Payroll
    {
        if ($cycle->estado === PayrollCycle::STATUS_GENERATED && ! $force) {
            throw new \RuntimeException('El ciclo ya ha sido generado. Use el flag "force" para regenerar.');
        }

        if ($cycle->estado === PayrollCycle::STATUS_CLOSED) {
            throw new \RuntimeException('No se puede regenerar la nomina de un ciclo cerrado.');
        }

        return DB::transaction(function () use ($employee, $cycle) {
            $existingPayroll = Payroll::query()
                ->where('employee_id', $employee->id)
                ->where('payroll_cycle_id', $cycle->id)
                ->lockForUpdate()
                ->first();

            $shifts = $cycle->shifts()
                ->where('employee_id', $employee->id)
                ->where('status', Shift::STATUS_APPROVED)
                ->where('is_voided', false)
                ->with('calculation')
                ->get();

            if ($shifts->isEmpty()) {
                if ($existingPayroll) {
                    $existingPayroll->details()->delete();
                    $existingPayroll->delete();
                }

                return null;
            }

            $contract = $employee->resolveActiveContract($cycle->fecha_fin);
            if (! $contract) {
                throw new \RuntimeException("No se encontro un contrato activo para el empleado {$employee->nombre}.");
            }

            $laborRule = LaborRule::query()
                ->whereDate('vigente_desde', '<=', $cycle->fecha_fin)
                ->orderByDesc('vigente_desde')
                ->first();

            if (! $laborRule) {
                throw new \RuntimeException('No existe una regla laboral vigente para el ciclo solicitado.');
            }

            $diasCiclo = $cycle->fecha_inicio->diffInDays($cycle->fecha_fin) + 1;
            $salarioBaseProporcional = round(($contract->salario_base / 30) * $diasCiclo, 2);
            $hourlyRate = round($contract->salario_base / 240, 2);

            $items = $this->consolidator->consolidate($shifts, $hourlyRate);
            $totalRecargosTurnos = (float) $items
                ->reject(fn (array $item): bool => $item['concept'] === 'salary_base')
                ->sum('amount');

            $ibc = $salarioBaseProporcional + $totalRecargosTurnos;
            $deduccionesData = $this->calculateDeductions->execute($ibc, $laborRule);

            $snapshot = [
                'salario_base' => (float) $contract->salario_base,
                'tipo_contrato' => 'termino_fijo',
                'horas_por_dia' => $laborRule->horas_max_diarias,
                'horas_mensuales' => 240,
                'reglas_laborales' => $laborRule->toArray(),
                'timestamp_calculo' => now()->toDateTimeString(),
            ];

            $payroll = Payroll::updateOrCreate(
                ['employee_id' => $employee->id, 'payroll_cycle_id' => $cycle->id],
                [
                    'salario_base_pagado' => $salarioBaseProporcional,
                    'recargos_pagados' => $totalRecargosTurnos,
                    'deduccion_salud' => $deduccionesData['salud'],
                    'deduccion_pension' => $deduccionesData['pension'],
                    'total_pagado' => $ibc,
                    'neto_pagado' => $ibc - $deduccionesData['total_deducciones'],
                    'tipo_pago' => $diasCiclo,
                    'fecha_pago' => $cycle->fecha_pago,
                    'estado' => $existingPayroll?->estado ?? Payroll::STATUS_PENDING,
                    'closed_at' => $existingPayroll?->closed_at,
                    'version' => $existingPayroll ? $existingPayroll->version + 1 : 1,
                    'calculation_snapshot' => $existingPayroll && $existingPayroll->calculation_snapshot
                        ? $existingPayroll->calculation_snapshot
                        : $snapshot,
                ]
            );

            $payroll->details()->delete();

            $payroll->details()->create([
                'concept' => 'salary_base',
                'label' => 'Sueldo base proporcional',
                'type' => PayrollDetail::TYPE_EARNING,
                'amount' => $salarioBaseProporcional,
            ]);

            foreach ($items as $item) {
                if ($item['concept'] === 'salary_base') {
                    continue;
                }

                $payroll->details()->create($item);
            }

            $payroll->details()->createMany([
                [
                    'concept' => 'health_deduction',
                    'label' => 'Deduccion Salud',
                    'type' => PayrollDetail::TYPE_DEDUCTION,
                    'amount' => $deduccionesData['salud'],
                ],
                [
                    'concept' => 'pension_deduction',
                    'label' => 'Deduccion Pension',
                    'type' => PayrollDetail::TYPE_DEDUCTION,
                    'amount' => $deduccionesData['pension'],
                ],
            ]);

            $totalIngresos = round((float) $payroll->details()->where('type', PayrollDetail::TYPE_EARNING)->sum('amount'), 2);
            $totalEgresos = round((float) $payroll->details()->where('type', PayrollDetail::TYPE_DEDUCTION)->sum('amount'), 2);
            $netoCalculado = round($totalIngresos - $totalEgresos, 2);
            $netoRegistrado = round((float) $payroll->neto_pagado, 2);

            if ($netoCalculado !== $netoRegistrado) {
                throw new \RuntimeException("Error de integridad: la suma de los detalles ({$netoCalculado}) no coincide con el neto pagado ({$netoRegistrado}).");
            }

            return $payroll;
        });
    }
}
