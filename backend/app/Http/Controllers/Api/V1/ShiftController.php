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

        // Si es empleado, forzamos el filtro de sus propios turnos
        if (!$request->user()->isAdmin()) {
            $employeeId = $request->user()->employee?->id;
            if (!$employeeId) {
                return response()->json(['errors' => ['Perfil de empleado no encontrado.']], 403);
            }
            $query->where('employee_id', $employeeId);
        } else {
            // Admin puede filtrar opcionalmente
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
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
        
        // Blindaje contra Spoofing: Forzamos el employee_id desde el perfil del usuario autenticado si es empleado
        if (!$user->isAdmin()) {
            $employeeId = $user->employee?->id;
            if (!$employeeId) {
                return response()->json(['errors' => ['Su cuenta no tiene un perfil de empleado asociado.']], 403);
            }
            $data['employee_id'] = $employeeId;
            $data['status'] = Shift::STATUS_PENDING;
        } else {
            // Admin puede crear turnos directamente aprobados
            $data['status'] = Shift::STATUS_APPROVED;
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        }

        $data['user_id'] = $user->id;

        // Trazabilidad de reemplazo (Fase 4 Gold Standard)
        if ($request->has('voids_shift_id')) {
            $oldShift = Shift::findOrFail($request->voids_shift_id);
            
            if (!$oldShift->is_voided) {
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
