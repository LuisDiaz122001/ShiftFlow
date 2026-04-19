<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CalculateShiftAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShiftRequest;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Shift::class);

        $query = Shift::with(['employee', 'calculation']);

        if ($request->user()->isAdmin()) {
            // Admin puede filtrar opcionalmente por empleado
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->integer('employee_id'));
            }
        } else {
            // Employee: siempre filtramos por su propio perfil
            $employee = $request->user()->requireEmployee();
            $query->where('employee_id', $employee->id);
        }

        return response()->json($query->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftRequest $request, CalculateShiftAction $calculateShift)
    {
        $user = $request->user();
        $data = $request->validated();

        // Blindaje de Identidad: employee_id SIEMPRE deriva del servidor
        if ($user->isAdmin()) {
            // Admin crea turnos directamente aprobados (employee_id viene del request)
            $data['status']      = Shift::STATUS_APPROVED;
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        } else {
            // Employee: inyectamos su propio employee_id y forzamos pending
            $employee            = $user->requireEmployee();
            $data['employee_id'] = $employee->id;
            $data['status']      = Shift::STATUS_PENDING;
        }

        $data['user_id'] = $user->id;

        // Trazabilidad de reemplazo (Gold Standard)
        if ($request->has('voids_shift_id')) {
            $oldShift = Shift::findOrFail($request->voids_shift_id);

            if (! $oldShift->is_voided) {
                return response()->json(['errors' => ['El turno original debe ser anulado antes de registrar un reemplazo.']], 422);
            }

            if ($oldShift->employee_id !== $data['employee_id']) {
                return response()->json(['errors' => ['El turno correctivo debe pertenecer al mismo empleado.']], 422);
            }

            $data['voids_shift_id'] = $oldShift->id;
        }

        $shift = Shift::create($data);

        try {
            $calculateShift->execute($shift);

            return response()->json([
                'data' => $shift->load('calculation'),
                'meta' => ['message' => 'Turno registrado correctamente.'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    /**
     * Aprueba un turno pendiente.
     */
    public function approve(Shift $shift, Request $request)
    {
        $this->authorize('approve', $shift);

        try {
            $shift->approve($request->user());
            return response()->json([
                'data' => $shift,
                'meta' => ['message' => 'Turno aprobado exitosamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    /**
     * Rechaza un turno pendiente.
     */
    public function reject(Shift $shift, Request $request)
    {
        $this->authorize('reject', $shift);

        try {
            $shift->reject($request->user());
            return response()->json([
                'data' => $shift,
                'meta' => ['message' => 'Turno rechazado exitosamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    /**
     * Anula un turno aprobado.
     */
    public function void(Shift $shift, Request $request)
    {
        $this->authorize('void', $shift);

        try {
            $shift->void($request->user());
            return response()->json([
                'data' => $shift,
                'meta' => ['message' => 'Turno anulado correctamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }
}
