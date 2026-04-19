<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProcessPayrollCycleAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PayrollCycleResource;
use App\Models\PayrollCycle;
use Illuminate\Http\Request;

class PayrollCycleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PayrollCycle::class);
        
        $validated = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'fecha_pago' => 'required|date|after_or_equal:fecha_fin',
        ]);

        $cycle = PayrollCycle::create($validated);

        return new PayrollCycleResource($cycle);
    }

    /**
     * Display the specified resource.
     */
    public function show(PayrollCycle $payrollCycle)
    {
        return new PayrollCycleResource($payrollCycle);
    }

    /**
     * Procesa la liquidación del ciclo.
     */
    public function process(PayrollCycle $payrollCycle, ProcessPayrollCycleAction $processAction, Request $request)
    {
        $this->authorize('process', $payrollCycle);
        
        try {
            $processAction->execute($payrollCycle, $request->boolean('force'));
            
            return response()->json([
                'data' => new PayrollCycleResource($payrollCycle->refresh()),
                'meta' => ['message' => 'Ciclo procesado exitosamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    /**
     * Cierra definitivamente el periodo.
     */
    public function close(PayrollCycle $payrollCycle)
    {
        $this->authorize('close', $payrollCycle);
        
        try {
            $payrollCycle->transitionTo(PayrollCycle::STATUS_CLOSED);
            
            return response()->json([
                'data' => new PayrollCycleResource($payrollCycle),
                'meta' => ['message' => 'Ciclo cerrado definitivamente.'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}
