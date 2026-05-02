<?php

namespace App\Services;

use App\Actions\GenerateEmployeePayrollForPeriodAction;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\PayrollLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly GenerateEmployeePayrollForPeriodAction $generateAction
    ) {}

    public function generateForEmployee(Employee $employee, CarbonInterface $start, CarbonInterface $end, bool $force = false): ?Payroll
    {
        try {
            $payroll = $this->generateAction->execute($employee, $start, $end, $force);

            if ($payroll) {
                $payroll->update([
                    'created_by' => auth()->id(),
                    'ip_address' => request()->ip(),
                ]);

                PayrollLog::log($payroll->id, 'create', [
                    'employee_id' => $payroll->employee_id,
                    'total_amount' => $payroll->total_amount,
                ]);
            }

            return $payroll;
        } catch (\Exception $e) {
            PayrollLog::log(null, 'blocked_attempt', [
                'reason' => 'Error durante la generación de nómina.',
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'period' => $start->toDateString() . ' - ' . $end->toDateString(),
            ]);
            throw $e;
        }
    }

    /**
     * Genera nóminas masivamente para todos los empleados con turnos en el periodo.
     */
    public function bulkGenerate(CarbonInterface $start, CarbonInterface $end): array
    {
        $employees = Employee::whereHas('shifts', function ($query) use ($start, $end) {
            $query->whereBetween('fecha_inicio', [$start, $end])
                ->where('status', 'approved');
        })->get();

        $results = [
            'success' => 0,
            'errors' => 0,
            'messages' => []
        ];

        foreach ($employees as $employee) {
            try {
                $payroll = $this->generateForEmployee($employee, $start, $end);
                if ($payroll) {
                    $results['success']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $results['messages'][] = "Error con {$employee->nombre}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    public function updateStatus(Payroll $payroll, string $newStatus, ?string $ip = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $newStatus, $ip) {
            $user = auth()->user();

            if (!$user || !$user->isAdmin()) {
                PayrollLog::log($payroll->id, 'blocked_attempt', ['reason' => 'Intento de cambio de estado por no-admin.']);
                throw new \RuntimeException('Solo los administradores pueden cambiar el estado de la nómina.');
            }

            if ($payroll->estado === Payroll::STATUS_PAID) {
                PayrollLog::log($payroll->id, 'blocked_attempt', ['reason' => 'Intento de modificar nómina pagada.']);
                throw new \RuntimeException('No se puede modificar una nómina que ya ha sido pagada.');
            }

            if ($newStatus === Payroll::STATUS_CANCELLED && $payroll->estado !== Payroll::STATUS_PENDING) {
                PayrollLog::log($payroll->id, 'blocked_attempt', ['reason' => 'Intento de cancelar nómina no-pendiente.']);
                throw new \RuntimeException('Solo se pueden cancelar nóminas que estén en estado pendiente.');
            }

            if ($newStatus === Payroll::STATUS_PAID && $payroll->estado !== Payroll::STATUS_PENDING) {
                throw new \RuntimeException('Solo se pueden marcar como pagadas las nóminas pendientes.');
            }

            $oldStatus = $payroll->estado;
            
            $attributes = [
                'estado' => $newStatus,
                'updated_by' => $user->id,
                'ip_address' => $ip ?? request()->ip(),
            ];

            if ($newStatus === Payroll::STATUS_PAID) {
                $attributes['paid_at'] = now();
                $attributes['paid_by'] = $user->id;
            }

            $payroll->update($attributes);

            PayrollLog::log($payroll->id, $newStatus === Payroll::STATUS_PAID ? 'pay' : 'cancel', [
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            return $payroll;
        });
    }

    /**
     * Paga múltiples nóminas simultáneamente.
     */
    public function bulkPay(array $payrollIds): array
    {
        $results = ['success' => 0, 'errors' => 0];
        
        foreach ($payrollIds as $id) {
            try {
                $payroll = Payroll::findOrFail($id);
                $this->updateStatus($payroll, Payroll::STATUS_PAID);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Obtiene un resumen financiero de las nóminas pagadas.
     */
    public function getFinancialSummary(): array
    {
        $paidPayrolls = Payroll::query()
            ->where('estado', Payroll::STATUS_PAID);

        $totalPaid = (float) $paidPayrolls->sum('total_amount');
        $totalHours = (float) $paidPayrolls->sum('total_hours');
        $employeeCount = (int) $paidPayrolls->distinct('employee_id')->count('employee_id');
        $avgPayPerEmployee = $employeeCount > 0 ? round($totalPaid / $employeeCount, 2) : 0;

        $periodStart = now()->subMonths(11)->startOfMonth();
        
        // Formato de fecha dependiente del driver
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', fecha_pago)" : "DATE_FORMAT(fecha_pago, '%Y-%m')";
        
        $monthlyRaw = Payroll::query()
            ->where('estado', Payroll::STATUS_PAID)
            ->whereDate('fecha_pago', '>=', $periodStart)
            ->selectRaw("$monthFormat as month")
            ->selectRaw('ROUND(SUM(total_amount), 2) as total_paid')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $monthlyPayments = [];
        for ($offset = 0; $offset < 12; $offset++) {
            $month = $periodStart->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $monthlyPayments[] = [
                'month' => $key,
                'label' => $month->translatedFormat('M Y'), // Usar translatedFormat para soporte multiidioma
                'total_paid' => (float) ($monthlyRaw[$key]->total_paid ?? 0),
            ];
        }

        $topEmployees = Employee::query()
            ->select(['employees.id', 'employees.nombre', 'employees.documento', DB::raw('ROUND(SUM(payrolls.total_amount), 2) as total_cost')])
            ->join('payrolls', 'employees.id', '=', 'payrolls.employee_id')
            ->where('payrolls.estado', Payroll::STATUS_PAID)
            ->groupBy('employees.id', 'employees.nombre', 'employees.documento')
            ->orderByDesc('total_cost')
            ->limit(5)
            ->get();

        return [
            'summary' => [
                'total_paid' => $totalPaid,
                'total_hours' => $totalHours,
                'avg_pay_per_employee' => $avgPayPerEmployee,
                'employee_count' => $employeeCount,
                'top_employee_name' => optional($topEmployees->first())->nombre,
            ],
            'monthlyPayments' => $monthlyPayments,
            'topEmployees' => $topEmployees->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->nombre,
                'documento' => $employee->documento,
                'total_cost' => (float) $employee->total_cost,
            ]),
        ];
    }

    /**
     * Cierra un ciclo de nómina de forma inmutable.
     */
    public function closeCycle(PayrollCycle $cycle): void
    {
        DB::transaction(function () use ($cycle) {
            $user = auth()->user();
            if (!$user || !$user->isAdmin()) {
                throw new \RuntimeException('Solo los administradores pueden cerrar periodos de nómina.');
            }

            if ($cycle->estado === PayrollCycle::STATUS_CLOSED) {
                return;
            }

            // Validar que todas las nóminas del ciclo estén pagadas o canceladas antes de cerrar
            $pendingCount = $cycle->payrolls()->where('estado', Payroll::STATUS_PENDING)->count();
            if ($pendingCount > 0) {
                throw new \RuntimeException("No se puede cerrar el periodo. Existen {$pendingCount} nóminas pendientes.");
            }

            $cycle->transitionTo(PayrollCycle::STATUS_CLOSED);

            PayrollLog::log(null, 'close_period', [
                'cycle_id' => $cycle->id,
                'period' => $cycle->fecha_inicio->toDateString() . ' - ' . $cycle->fecha_fin->toDateString(),
            ]);
        });
    }

    /**
     * Verifica si un ciclo está cerrado.
     */
    public function isCycleClosed(PayrollCycle $cycle): bool
    {
        return $cycle->estado === PayrollCycle::STATUS_CLOSED;
    }

    /**
     * Obtiene el resumen de los ciclos de nómina.
     */
    public function getCyclesSummary()
    {
        return PayrollCycle::query()
            ->withCount(['payrolls as total_payrolls'])
            ->withSum(['payrolls as total_amount'], 'total_amount')
            ->orderByDesc('fecha_inicio')
            ->paginate(12);
    }
}
