<?php

namespace App\Http\Controllers;

use App\Actions\GeneratePayrollPdfAction;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollLog;
use App\Models\Shift;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollWebController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Payroll::query()
            ->with(['employee.user', 'cycle'])
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id');

        if (! $user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR])) {
            $query->where('employee_id', $user->requireEmployee()->id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('period_start')) {
            $query->whereHas('cycle', fn ($cycle) => $cycle->whereDate('fecha_inicio', '>=', $request->period_start));
        }

        if ($request->filled('period_end')) {
            $query->whereHas('cycle', fn ($cycle) => $cycle->whereDate('fecha_fin', '<=', $request->period_end));
        }

        $employees = null;
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR])) {
            $employees = Employee::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        }

        return Inertia::render('Payrolls/Index', [
            'payrolls' => $query->paginate(10)->withQueryString(),
            'employees' => $employees,
            'filters' => $request->only(['employee_id', 'estado', 'period_start', 'period_end']),
            'canGenerate' => $user->can('create', Payroll::class),
        ]);
    }

    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', Payroll::class);

        $pendingPayrolls = Payroll::where('estado', Payroll::STATUS_PENDING)->get();
        $paidThisMonth = Payroll::where('estado', Payroll::STATUS_PAID)
            ->whereMonth('paid_at', now()->month)
            ->sum('total_amount');

        $activeCycle = \App\Models\PayrollCycle::where('estado', \App\Models\PayrollCycle::STATUS_OPEN)
            ->orderBy('fecha_fin', 'desc')
            ->first();

        return Inertia::render('Payrolls/Dashboard', [
            'stats' => [
                'pending_count' => $pendingPayrolls->count(),
                'pending_amount' => $pendingPayrolls->sum('total_amount'),
                'paid_this_month' => (float) $paidThisMonth,
                'active_cycle' => $activeCycle,
            ],
            'recent_pending' => Payroll::where('estado', Payroll::STATUS_PENDING)
                ->with('employee')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        try {
            $payroll = $this->payrollService->generateForEmployee(
                $employee,
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end'])
            );

            if (! $payroll) {
                return back()->withErrors(['error' => 'No existen turnos aprobados para el periodo seleccionado.']);
            }

            return redirect()->route('payrolls.index', $request->only(['employee_id', 'period_start', 'period_end']))
                ->with('success', 'Nómina generada correctamente.');

        } catch (\Exception $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function show(Request $request, Payroll $payroll): Response
    {
        $this->authorize('view', $payroll);

        $payroll->load(['employee.user', 'cycle', 'details']);

        $shifts = Shift::query()
            ->where('employee_id', $payroll->employee_id)
            ->where('payroll_cycle_id', $payroll->payroll_cycle_id)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->orderBy('fecha_inicio')
            ->get();

        return Inertia::render('Payrolls/Show', [
            'payroll' => $payroll,
            'shifts' => $shifts,
        ]);
    }

    public function updateStatus(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('update', $payroll);

        $validated = $request->validate([
            'estado' => ['required', Rule::in([Payroll::STATUS_PAID, Payroll::STATUS_CANCELLED])],
        ]);

        try {
            $this->payrollService->updateStatus($payroll, $validated['estado']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Estado de nómina actualizado correctamente.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $results = $this->payrollService->bulkGenerate(
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end'])
        );

        return back()->with('success', "Proceso completado: {$results['success']} nóminas generadas. {$results['errors']} errores.");
    }

    public function bulkPay(Request $request): RedirectResponse
    {
        $this->authorize('updateAny', Payroll::class);

        $validated = $request->validate([
            'payroll_ids' => ['required', 'array'],
            'payroll_ids.*' => ['exists:payrolls,id'],
        ]);

        $results = $this->payrollService->bulkPay($validated['payroll_ids']);

        return back()->with('success', "Proceso completado: {$results['success']} nóminas pagadas.");
    }

    public function financialSummary(Request $request): Response
    {
        $data = $this->payrollService->getFinancialSummary();

        return Inertia::render('Payrolls/Financial', $data);
    }

    public function exportPdf(Payroll $payroll, GeneratePayrollPdfAction $generatePdf)
    {
        $this->authorize('view', $payroll);

        try {
            $pdf = $generatePdf->execute($payroll->id);

            return $pdf->download("nomina-{$payroll->id}.pdf");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
