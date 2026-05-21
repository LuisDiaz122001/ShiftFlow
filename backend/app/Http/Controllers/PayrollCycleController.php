<?php

namespace App\Http\Controllers;

use App\Actions\ProcessPayrollCycleAction;
use App\Http\Requests\StorePayrollCycleRequest;
use App\Models\PayrollCycle;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollCycleController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PayrollCycle::class);

        if (! auth()->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden gestionar periodos.');
        }

        return Inertia::render('Payrolls/Cycles', [
            'cycles' => $this->payrollService->getCyclesSummary(),
        ]);
    }

    public function store(StorePayrollCycleRequest $request): RedirectResponse
    {
        $this->authorize('create', PayrollCycle::class);

        PayrollCycle::create([
            ...$request->validated(),
            'estado' => PayrollCycle::STATUS_OPEN,
        ]);

        return redirect()
            ->route('payrolls.periods')
            ->with('success', 'Periodo de nómina creado correctamente.');
    }

    public function process(PayrollCycle $cycle, ProcessPayrollCycleAction $processAction, Request $request): RedirectResponse
    {
        $this->authorize('process', $cycle);

        if ($cycle->estado === PayrollCycle::STATUS_CLOSED) {
            return back()->withErrors(['error' => 'No se puede procesar un periodo cerrado.']);
        }

        if ($cycle->estado === PayrollCycle::STATUS_GENERATED && ! $request->boolean('force')) {
            return back()->withErrors(['error' => 'El periodo ya fue procesado. Confirme regeneración para forzar.']);
        }

        try {
            $processAction->execute($cycle, $request->boolean('force'));

            return back()->with('success', 'Periodo procesado correctamente. Las nóminas fueron generadas o actualizadas.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function close(PayrollCycle $cycle): RedirectResponse
    {
        $this->authorize('close', $cycle);

        if (! auth()->user()->isAdmin()) {
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
