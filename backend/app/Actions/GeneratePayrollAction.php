<?php

namespace App\Actions;

use App\Models\Employee;
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

    /**
     * Genera o actualiza el registro de nómina acumulado para un empleado en un ciclo.
     */
    public function execute(Employee $employee, PayrollCycle $cycle, bool $force = false): ?Payroll
    {
        // Ajuste 3: Regeneración controlada
        if ($cycle->estado === PayrollCycle::STATUS_GENERATED && ! $force) {
            throw new \RuntimeException('El ciclo ya ha sido generado. Use el flag "force" para regenerar.');
        }

        if ($cycle->estado === PayrollCycle::STATUS_CLOSED) {
            throw new \RuntimeException('No se puede regenerar la nómina de un ciclo cerrado.');
        }

        return DB::transaction(function () use ($employee, $cycle) {
            // Ajuste 1: Bloqueo de registros existentes para evitar concurrencia
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('payroll_cycle_id', $cycle->id)
                ->lockForUpdate()
                ->first();

            $shifts = $cycle->shifts()
                ->where('employee_id', $employee->id)
                ->where('status', Shift::STATUS_APPROVED)
                ->where('is_voided', false)
                ->whereHas('calculation')
                ->with('calculation')
                ->get();

            if ($shifts->isEmpty()) {
                if ($existingPayroll) {
                    $existingPayroll->details()->delete();
                    $existingPayroll->delete();
                }
                return null;
            }

            // Resolver contrato y regla laboral para el snapshot
            $contract = $employee->resolveActiveContract($cycle->fecha_fin);
            if (! $contract) {
                throw new \RuntimeException("No se encontró un contrato activo para el empleado {$employee->nombre}.");
            }

            $laborRule = \App\Models\LaborRule::query()
                ->whereDate('vigente_desde', '<=', $cycle->fecha_fin)
                ->orderByDesc('vigente_desde')
                ->first();

            $diasCiclo = $cycle->fecha_inicio->diffInDays($cycle->fecha_fin) + 1;
            $salarioBaseProporcional = round(($contract->salario_base / 30) * $diasCiclo, 2);

            // 1. Consolidar conceptos de turnos
            $items = $this->consolidator->consolidate($shifts->pluck('calculation'));
            
            // 2. Calcular Totales de Turnos (Solo lo que no sea sueldo base regular)
            $totalRecargosTurnos = (float) $items->reject(fn($i) => $i['concept'] === 'salary_base')->sum('amount');
            
            // 3. Calcular IBC y Deducciones
            $ibc = $salarioBaseProporcional + $totalRecargosTurnos;
            $deduccionesData = $this->calculateDeductions->execute($ibc, $laborRule);

            // 4. Preparar Snapshot (Ajuste 1 Final)
            $snapshot = [
                'salario_base' => $contract->salario_base,
                'tipo_contrato' => 'termino_fijo', // O el campo real que tengas
                'horas_por_dia' => $laborRule->horas_max_diarias,
                'horas_mensuales' => 240,
                'reglas_laborales' => $laborRule->toArray(),
                'timestamp_calculo' => now()->toDateTimeString(),
            ];

            // 5. Upsert de la Nómina (Ajuste 3: Versionado)
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
                    'version' => $existingPayroll ? $existingPayroll->version + 1 : 1,
                    // El snapshot solo se guarda si es nulo (Ajuste 4 Final: Inmutable)
                    'calculation_snapshot' => $existingPayroll && $existingPayroll->calculation_snapshot 
                        ? $existingPayroll->calculation_snapshot 
                        : $snapshot,
                ]
            );

            // 6. Sincronizar Detalles (Capa Contable)
            $payroll->details()->delete();

            // Ajuste Final: El salario base proporcional es el primer item contable
            $payroll->details()->create([
                'concept' => 'salary_base',
                'label' => 'Sueldo base proporcional',
                'type' => PayrollDetail::TYPE_EARNING,
                'amount' => $salarioBaseProporcional,
            ]);

            // Agregar conceptos de turnos (Recargos / Extras)
            foreach ($items as $item) {
                if ($item['concept'] === 'salary_base') {
                    continue;
                }
                $payroll->details()->create($item);
            }

            // Agregar deducciones como ítems contables
            $payroll->details()->createMany([
                [
                    'concept' => 'health_deduction',
                    'label' => 'Deducción Salud',
                    'type' => PayrollDetail::TYPE_DEDUCTION,
                    'amount' => $deduccionesData['salud'],
                ],
                [
                    'concept' => 'pension_deduction',
                    'label' => 'Deducción Pensión',
                    'type' => PayrollDetail::TYPE_DEDUCTION,
                    'amount' => $deduccionesData['pension'],
                ],
            ]);

            // 6. Validación Final de Integridad Matemática (Ajuste 4 Final)
            $totalIngresos = round((float) $payroll->details()->where('type', 'earning')->sum('amount'), 2);
            $totalEgresos = round((float) $payroll->details()->where('type', 'deduction')->sum('amount'), 2);
            $netoCalculado = round($totalIngresos - $totalEgresos, 2);
            $netoRegistrado = round((float) $payroll->neto_pagado, 2);
            
            if ($netoCalculado !== $netoRegistrado) {
                throw new \RuntimeException("Error de integridad: la suma de los detalles ({$netoCalculado}) no coincide con el neto pagado ({$netoRegistrado}).");
            }

            return $payroll;
        });
    }
}
