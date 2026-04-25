<?php

namespace App\Http\Controllers;

use App\Actions\CalculateShiftAction;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ShiftWebController extends Controller
{
    /**
     * Muestra el listado de turnos del empleado autenticado.
     * Admin ve todos los turnos (sin restricción de employee).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Shift::with(['employee:id,nombre'])
            ->latest('fecha_inicio');

        if ($user->isAdmin()) {
            // Admin ve todos con filtro opcional
            if ($request->has('status')) {
                $query->where('status', $request->string('status'));
            }
        } else {
            // Employee: solo sus propios turnos
            $employee = $user->requireEmployee();
            $query->where('employee_id', $employee->id);
        }

        $shifts = $query->paginate(15)->withQueryString();

        return Inertia::render('Shifts/Index', [
            'shifts'   => $shifts,
            'statuses' => [Shift::STATUS_PENDING, Shift::STATUS_APPROVED, Shift::STATUS_REJECTED],
            'filters'  => $request->only('status'),
        ]);
    }

    /**
     * Almacena un nuevo turno para el usuario autenticado.
     * El employee_id SIEMPRE se deriva del servidor.
     */
    public function store(Request $request, CalculateShiftAction $calculateShift)
    {
        $user = $request->user();

        $data = $request->validate([
            'fecha_inicio'   => ['required', 'date'],
            'fecha_fin'      => ['required', 'date', 'after:fecha_inicio'],
            // employee_id NO se acepta del request (inyección forzada)
        ]);

        // Inyección de identidad desde el servidor
        if ($user->isAdmin()) {
            // Admin puede especificar el empleado
            $request->validate(['employee_id' => ['required', 'integer', 'exists:employees,id']]);
            $data['employee_id'] = $request->integer('employee_id');
            $data['status']      = Shift::STATUS_APPROVED;
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        } else {
            $employee            = $user->requireEmployee();
            $data['employee_id'] = $employee->id;
            $data['status']      = Shift::STATUS_PENDING;
        }

        $data['user_id'] = $user->id;

        try {
            $shift = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $calculateShift) {
                $shift = Shift::create($data);
                $calculateShift->execute($shift);
                return $shift;
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar el turno: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Turno registrado correctamente. Estado: ' . $shift->status . '.');
    }
}
