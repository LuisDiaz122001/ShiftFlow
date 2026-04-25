<?php

namespace App\Actions;

use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class CalculatePayrollSummaryAction
{
    /**
     * Consolidates shift data for a given employee and period using Shift as Unique Source of Truth.
     * 
     * @param int $employeeId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return array{total_hours: float, diurnas_hours: float, nocturnas_hours: float, total_pago: float, shift_ids: array}
     */
    public function execute(int $employeeId, string $startDate, string $endDate): array
    {
        $shiftsQuery = Shift::query()
            ->where('employee_id', $employeeId)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->whereDate('fecha_inicio', '>=', $startDate)
            ->whereDate('fecha_fin', '<=', $endDate)
            // Hardening: Excluir turnos que ya están en una nómina cerrada
            ->whereDoesntHave('payrolls', function($q) {
                $q->whereIn('estado', [Payroll::STATUS_LOCKED, Payroll::STATUS_PAID]);
            });

        $shiftIds = $shiftsQuery->pluck('id')->toArray();

        $summary = $shiftsQuery->select([
                DB::raw('SUM(total_hours) as total_hours'),
                DB::raw('SUM(diurnas_hours) as diurnas_hours'),
                DB::raw('SUM(nocturnas_hours) as nocturnas_hours'),
                DB::raw('SUM(total_pago) as total_pago')
            ])
            ->first();

        return [
            'total_hours' => round((float) ($summary->total_hours ?? 0), 2),
            'diurnas_hours' => round((float) ($summary->diurnas_hours ?? 0), 2),
            'nocturnas_hours' => round((float) ($summary->nocturnas_hours ?? 0), 2),
            'total_pago' => round((float) ($summary->total_pago ?? 0), 2),
            'shift_ids' => $shiftIds,
        ];
    }
}
