<?php

namespace App\Http\Controllers;

use App\Actions\CalculateShiftAction;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ShiftWebController extends Controller
{
    /**
     * Muestra el listado de turnos del empleado autenticado.
     * Admin ve todos los turnos (sin restricción de employee).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Shift::with(['employee:id,nombre'])
            ->latest('fecha_inicio');

        if ($user->isAdmin()) {
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
        } else {
            $employee = $user->requireEmployee();
            $query->where('employee_id', $employee->id);
        }

        $shifts = $query->paginate(15)->withQueryString();

        $employees = $user->isAdmin()
            ? Employee::query()->orderBy('nombre')->get(['id', 'nombre'])
            : [];

        return Inertia::render('Shifts/Index', [
            'shifts' => $shifts,
            'statuses' => [Shift::STATUS_PENDING, Shift::STATUS_APPROVED, Shift::STATUS_REJECTED],
            'filters' => $request->only('status'),
            'employees' => $employees,
            'canModerate' => $user->isAdmin(),
        ]);
    }

    /**
     * Almacena un nuevo turno para el usuario autenticado.
     * El employee_id SIEMPRE se deriva del servidor (salvo admin).
     */
    public function store(Request $request, CalculateShiftAction $calculateShift): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($user->isAdmin()) {
            $request->validate(['employee_id' => ['required', 'integer', 'exists:employees,id']]);
            $data['employee_id'] = $request->integer('employee_id');
            $data['status'] = Shift::STATUS_APPROVED;
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        } else {
            $employee = $user->requireEmployee();
            $data['employee_id'] = $employee->id;
            $data['status'] = Shift::STATUS_PENDING;
        }

        $data['user_id'] = $user->id;

        try {
            DB::transaction(function () use ($data, $calculateShift) {
                $shift = Shift::create($data);
                $calculateShift->execute($shift);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar el turno: '.$e->getMessage()]);
        }

        return back()->with('success', 'Turno registrado correctamente.');
    }

    public function approve(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('approve', Shift::class);

        try {
            $shift->approve($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Turno aprobado correctamente.');
    }

    public function reject(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('approve', Shift::class);

        try {
            $shift->reject($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Turno rechazado correctamente.');
    }

    public function void(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('void', $shift);

        try {
            $shift->void($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Turno anulado correctamente.');
    }
}
