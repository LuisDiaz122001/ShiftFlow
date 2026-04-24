<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CalculateShiftPaymentsAction;
use App\Actions\ClassifyShiftHoursAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShiftRequest;
use App\Http\Resources\V1\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Shift::class);

        $query = Shift::with(['employee']); // ❌ quitamos calculation

        if ($request->user()->isAdmin()) {
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->integer('employee_id'));
            }
        } else {
            $employee = $request->user()->requireEmployee();
            $query->where('employee_id', $employee->id);
        }

        return ShiftResource::collection($query->paginate());
    }

    public function store(
        StoreShiftRequest $request,
        ClassifyShiftHoursAction $classify,
        CalculateShiftPaymentsAction $calculatePayments
    ) {
        $user = $request->user();
        $data = $request->validated();

        if ($user->isAdmin()) {
            $data['status']      = Shift::STATUS_APPROVED;
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        } else {
            $employee            = $user->requireEmployee();
            $data['employee_id'] = $employee->id;
            $data['status']      = Shift::STATUS_PENDING;
        }

        $data['user_id'] = $user->id;

        if ($request->has('voids_shift_id')) {
            $oldShift = Shift::findOrFail($request->voids_shift_id);

            if (! $oldShift->is_voided) {
                return response()->json([
                    'message' => 'El turno original debe ser anulado antes de registrar un reemplazo.'
                ], 422);
            }

            if ($oldShift->employee_id !== $data['employee_id']) {
                return response()->json([
                    'message' => 'El turno correctivo debe pertenecer al mismo empleado.'
                ], 422);
            }

            $data['voids_shift_id'] = $oldShift->id;
        }

        $shift = Shift::create($data);
        $shift->load('employee'); // Necesario para obtener salario_base

        try {
            $classification = $classify(
                $shift->fecha_inicio,
                $shift->fecha_fin
            );

            $shift->total_hours     = $classification['total'];
            $shift->diurnas_hours   = $classification['diurnas'];
            $shift->nocturnas_hours = $classification['nocturnas'];

            // Cálculo financiero (No persistido en DB)
            $payments = $calculatePayments(
                (float) $shift->total_hours,
                (float) $shift->diurnas_hours,
                (float) $shift->nocturnas_hours,
                (float) $shift->employee->salario_base
            );

            $shift->valor_hora    = $payments['valor_hora'];
            $shift->pago_diurno   = $payments['pago_diurno'];
            $shift->pago_nocturno = $payments['pago_nocturno'];
            $shift->total_pago    = $payments['total_pago'];

            return (new ShiftResource($shift))->additional([
                'meta' => ['message' => 'Turno registrado correctamente.'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}