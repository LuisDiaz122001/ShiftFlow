<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CalculateShiftAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShiftRequest;
use App\Http\Resources\V1\ShiftResource;
use App\Models\Shift;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Shift::class);

        $query = Shift::with(['employee']);

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
        CalculateShiftAction $calculateShift
    ) {
        $user = $request->user();
        $data = $request->validated();

        if ($user->isAdmin()) {
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
            $shift = DB::transaction(function () use ($data, $calculateShift) {
                $shift = Shift::create($data);
                $calculateShift->execute($shift);

                return $shift;
            });

            return (new ShiftResource($shift))->additional([
                'meta' => ['message' => 'Turno registrado correctamente.'],
            ]);
        } catch (QueryException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Error al procesar el turno: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function approve(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('approve', Shift::class);

        try {
            $shift->approve($request->user());

            return response()->json([
                'data' => $this->serializeShift($shift->fresh()),
                'meta' => ['message' => 'Turno aprobado correctamente.'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'errors' => [$exception->getMessage()],
            ], 422);
        }
    }

    public function reject(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('approve', Shift::class);

        try {
            $shift->reject($request->user());

            return response()->json([
                'data' => $this->serializeShift($shift->fresh()),
                'meta' => ['message' => 'Turno rechazado correctamente.'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'errors' => [$exception->getMessage()],
            ], 422);
        }
    }

    public function void(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('void', $shift);

        try {
            $shift->void($request->user());

            return response()->json([
                'data' => $this->serializeShift($shift->fresh()),
                'meta' => ['message' => 'Turno anulado correctamente.'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'errors' => [$exception->getMessage()],
            ], 422);
        }
    }

    private function serializeShift(Shift $shift): array
    {
        return array_merge(
            (new ShiftResource($shift))->resolve(),
            [
                'approved_by' => $shift->approved_by,
                'approved_at' => $shift->approved_at,
                'rejected_by' => $shift->rejected_by,
                'rejected_at' => $shift->rejected_at,
                'is_voided' => (bool) $shift->is_voided,
                'voided_by' => $shift->voided_by,
                'voided_at' => $shift->voided_at,
            ]
        );
    }
}
