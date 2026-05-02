<?php

namespace App\Actions;

use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class GeneratePayrollPdfAction
{
    public function execute(int $payrollId): DomPDF
    {
        $payroll = Payroll::with(['employee.user', 'cycle', 'details'])->findOrFail($payrollId);

        if (
            $payroll->estado !== Payroll::STATUS_PAID
            && ! in_array($payroll->cycle?->estado, [PayrollCycle::STATUS_GENERATED, PayrollCycle::STATUS_CLOSED], true)
        ) {
            throw new \RuntimeException('Documento no disponible: la nomina aun no pertenece a un ciclo generado.');
        }

        $shifts = Shift::query()
            ->where('employee_id', $payroll->employee_id)
            ->where('payroll_cycle_id', $payroll->payroll_cycle_id)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->orderBy('fecha_inicio')
            ->get();

        return Pdf::loadView('payrolls.pdf', [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'user' => $payroll->employee->user,
            'cycle' => $payroll->cycle,
            'details' => $payroll->details,
            'shifts' => $shifts,
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait')
            ->setWarnings(false);
    }
}
