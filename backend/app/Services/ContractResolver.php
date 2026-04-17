<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use Carbon\CarbonInterface;
use RuntimeException;

class ContractResolver
{
    public function resolve(Employee $employee, CarbonInterface $date): Contract
    {
        $contract = $employee->resolveActiveContract($date);

        if (! $contract instanceof Contract) {
            throw new RuntimeException("No existe un contrato activo para el empleado {$employee->id} en la fecha {$date->toDateString()}.");
        }

        return $contract;
    }
}
