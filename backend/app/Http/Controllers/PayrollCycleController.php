<?php
namespace App\Http\Controllers;

use App\Models\PayrollCycle;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollCycleController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', \App\Models\Payroll::class);

        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden gestionar periodos.');
        }

        return Inertia::render('Payrolls/Cycles', [
            'cycles' => $this->payrollService->getCyclesSummary()
        ]);
    }

    public function close(PayrollCycle $cycle)
    {
        $this->authorize('update', \App\Models\Payroll::class); // Reusing payroll authorization logic

        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden cerrar periodos.');
        }

        try {
            $this->payrollService->closeCycle($cycle);
            return back()->with('success', 'Periodo cerrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
