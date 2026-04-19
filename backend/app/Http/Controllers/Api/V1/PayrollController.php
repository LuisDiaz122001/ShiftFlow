<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PayrollResource;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::with(['employee', 'cycle', 'details']);

        // Filtro de propiedad: Empleados solo ven lo suyo
        if (!$request->user()->isAdmin()) {
            $employeeId = $request->user()->employee?->id;
            if (!$employeeId) {
                return response()->json(['errors' => ['Perfil de empleado no encontrado.']], 403);
            }
            $query->where('employee_id', $employeeId);
        } else {
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
        }

        if ($request->has('payroll_cycle_id')) {
            $query->where('payroll_cycle_id', $request->payroll_cycle_id);
        }

        return PayrollResource::collection($query->paginate());
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll)
    {
        $this->authorize('view', $payroll);

        $payroll->load(['employee', 'cycle', 'details']);
        
        return new PayrollResource($payroll);
    }
}
