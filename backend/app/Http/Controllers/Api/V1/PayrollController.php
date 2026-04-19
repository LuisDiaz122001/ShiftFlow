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

        if ($request->user()->isAdmin()) {
            // Admin puede filtrar opcionalmente por empleado o ciclo
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->integer('employee_id'));
            }
        } else {
            // Employee: solo ve sus propias nóminas
            $employee = $request->user()->requireEmployee();
            $query->where('employee_id', $employee->id);
        }

        if ($request->has('payroll_cycle_id')) {
            $query->where('payroll_cycle_id', $request->integer('payroll_cycle_id'));
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
