<?php

namespace App\Actions;

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class GeneratePayrollPdfAction
{
    /**
     * Generates a PDF instance for a specific payroll settlement.
     * Only works for LOCKED or PAID payrolls (Accounting Integrity).
     *
     * @param int $payrollId
     * @return DomPDF
     * @throws \RuntimeException
     */
    public function execute(int $payrollId): DomPDF
    {
        $payroll = Payroll::with(['employee.user', 'shifts.calculation'])->findOrFail($payrollId);

        // Security check: Only locked/paid payrolls can be exported as official documents
        if (!$payroll->isLocked()) {
            throw new \RuntimeException('Documento No Disponible: Solo las nóminas cerradas pueden ser exportadas a PDF.');
        }

        $data = [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'user' => $payroll->employee->user,
            'shifts' => $payroll->shifts,
            'generated_at' => now(),
        ];

        return Pdf::loadView('payrolls.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);
    }
}
