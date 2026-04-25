<?php

namespace App\Http\Controllers;

use App\Actions\CalculatePayrollSummaryAction;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollWebController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Payrolls/Index', [
            'payrolls' => Payroll::with('employee.user')
                ->orderBy('fecha_fin', 'desc')
                ->paginate(10),
            'employees' => Employee::with('user')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CalculatePayrollSummaryAction $calculateSummary)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        // Check for duplicates
        $exists = Payroll::where('employee_id', $validated['employee_id'])
            ->where('fecha_inicio', $validated['fecha_inicio'])
            ->where('fecha_fin', $validated['fecha_fin'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Ya existe una nómina para este empleado en el periodo seleccionado.']);
        }

        // Aggregate data
        $summary = $calculateSummary->execute(
            $validated['employee_id'],
            $validated['fecha_inicio'],
            $validated['fecha_fin']
        );

        if ($summary['total_hours'] <= 0) {
            return back()->withErrors(['error' => 'No se encontraron turnos aprobados para este periodo.']);
        }

        // Create Payroll
        $payroll = Payroll::create([
            'employee_id' => $validated['employee_id'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'total_hours' => $summary['total_hours'],
            'diurnas_hours' => $summary['diurnas_hours'],
            'nocturnas_hours' => $summary['nocturnas_hours'],
            'total_pago' => $summary['total_pago'],
            'estado' => Payroll::STATUS_LOCKED,
            'audit_shift_ids' => $summary['shift_ids'], // Snapshot JSON
            'closed_at' => now(),
        ]);

        // Link shifts via pivot for relationship-based auditing
        $payroll->shifts()->sync($summary['shift_ids']);

        return redirect()->route('payrolls.index')->with('success', 'Nómina generada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll): Response
    {
        $payroll->load('employee.user');
        
        // Fetch the shifts included in this payroll for detail
        $shifts = Shift::with('calculation')
            ->where('employee_id', $payroll->employee_id)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->whereDate('fecha_inicio', '>=', $payroll->fecha_inicio)
            ->whereDate('fecha_fin', '<=', $payroll->fecha_fin)
            ->get();

        return Inertia::render('Payrolls/Show', [
            'payroll' => $payroll,
            'shifts' => $shifts
        ]);
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid(Payroll $payroll)
    {
        $payroll->update(['estado' => Payroll::STATUS_PAID]);
        return back()->with('success', 'Nómina marcada como pagada.');
    }

    /**
     * Export payroll to PDF.
     */
    public function exportPdf(int $id, \App\Actions\GeneratePayrollPdfAction $generatePdf)
    {
        try {
            $pdf = $generatePdf->execute($id);
            return $pdf->download("nomina-{$id}.pdf");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
