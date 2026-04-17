<?php

namespace App\Actions;

use App\Models\Shift;
use App\Models\ShiftCalculation;
use App\Services\ContractResolver;
use App\Services\PayrollCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CalculateShiftAction
{
    public function __construct(
        private readonly ContractResolver $contractResolver,
        private readonly PayrollCalculatorService $payrollCalculatorService,
        private readonly UpsertShiftCalculationAction $upsertShiftCalculationAction,
    ) {
    }

    public function handle(Shift $shift): ShiftCalculation
    {
        return DB::transaction(function () use ($shift): ShiftCalculation {
            $shift->loadMissing(['employee.contracts']);

            if (! $shift->employee) {
                throw new RuntimeException('El turno debe tener un empleado asociado para calcular y persistir la liquidacion.');
            }

            $this->contractResolver->resolve(
                $shift->employee,
                Carbon::parse($shift->fecha_inicio)
            );

            $calculation = $this->payrollCalculatorService->calculate($shift);

            return $this->upsertShiftCalculationAction->handle($shift, $calculation);
        });
    }
}
