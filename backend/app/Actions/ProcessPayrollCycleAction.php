<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\PayrollCycle;
use Illuminate\Support\Facades\DB;

class ProcessPayrollCycleAction
{
    public function __construct(
        private readonly GeneratePayrollAction $generatePayroll,
    ) {
    }

    /**
     * Orquesta el procesamiento completo de un ciclo de nómina con bloqueos de concurrencia.
     */
    public function execute(PayrollCycle $cycle, bool $force = false): void
    {
        DB::transaction(function () use ($cycle, $force) {
            // Ajuste 1: Bloqueo de Pesimismo sobre el ciclo
            $cycle = PayrollCycle::where('id', $cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cycle->isLockedForCalculation() && ! $force) {
                throw new \RuntimeException('El ciclo ya está liquidado (generated/closed). Use force para regenerar.');
            }

            // Obtener empleados que tienen turnos en este ciclo
            $employees = Employee::whereHas('shifts', function ($q) use ($cycle) {
                $q->where('payroll_cycle_id', $cycle->id);
            })->get();

            foreach ($employees as $employee) {
                $this->generatePayroll->execute($employee, $cycle, $force);
            }

            // Transición de estado (Ajuste 2 Final)
            if ($cycle->estado === PayrollCycle::STATUS_OPEN) {
                $cycle->transitionTo(PayrollCycle::STATUS_GENERATED);
            }
        });
    }
}
