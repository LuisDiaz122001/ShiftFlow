<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class GenerateEmployeePayrollForPeriodAction
{
    public function __construct(
        private readonly GeneratePayrollAction $generatePayroll,
    ) {
    }

    public function execute(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd, bool $force = false): ?Payroll
    {
        return DB::transaction(function () use ($employee, $periodStart, $periodEnd, $force) {
            // Concurrency Control: Check for existing active/paid payroll with lock
            $existing = Payroll::query()
                ->where('employee_id', $employee->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->where('estado', '!=', Payroll::STATUS_CANCELLED)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $force) {
                throw new \RuntimeException('Ya existe una nómina activa o pagada para este empleado en el periodo seleccionado.');
            }

            $cycle = PayrollCycle::whereDate('fecha_inicio', $periodStart->toDateString())
                ->whereDate('fecha_fin', $periodEnd->toDateString())
                ->first();

            if (! $cycle) {
                $cycle = PayrollCycle::create([
                    'fecha_inicio' => $periodStart->toDateString(),
                    'fecha_fin' => $periodEnd->toDateString(),
                    'fecha_pago' => $periodEnd->toDateString(),
                    'estado' => PayrollCycle::STATUS_OPEN,
                ]);
            }

            if ($cycle->estado === PayrollCycle::STATUS_CLOSED) {
                throw new \RuntimeException('No se puede generar nómina para un periodo que ya ha sido cerrado.');
            }

            $payroll = $this->generatePayroll->execute($employee, $cycle, $force);

            if (! $payroll) {
                return null;
            }

            $approvedShifts = $cycle->shifts()
                ->where('employee_id', $employee->id)
                ->where('status', Shift::STATUS_APPROVED)
                ->where('is_voided', false)
                ->get();

            $totalHours = round((float) $approvedShifts->sum('total_hours'), 2);
            $contract = $employee->resolveActiveContract($periodEnd);
            $hourlyRate = $contract ? round($contract->salario_base / 240, 2) : 0;
            $totalAmount = round($totalHours * $hourlyRate, 2);

            $payroll->update([
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_hours' => $totalHours,
                'hourly_rate' => $hourlyRate,
                'total_amount' => $totalAmount,
                'fecha_pago' => $periodEnd->toDateString(),
            ]);

            return $payroll;
        });
    }
}
