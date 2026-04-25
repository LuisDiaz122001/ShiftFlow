<?php

namespace App\Http\Controllers\Api\V1;

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
        \App\Actions\CalculateShiftAction $calculateShift
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

        try {
            $shift = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $calculateShift) {
                $shift = Shift::create($data);
                $calculateShift->execute($shift);
                return $shift;
            });

            return (new ShiftResource($shift))->additional([
                'meta' => ['message' => 'Turno registrado correctamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el turno: ' . $e->getMessage()
            ], 422);
        }
    }
}