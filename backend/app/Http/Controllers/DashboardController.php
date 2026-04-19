<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftCalculation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Muestra el resumen de métricas del dashboard según el rol del usuario.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        // 1. Obtener ID de empleado si aplica (Blindaje de Ownership)
        $employeeId = $user->employee?->id;

        // 2. Base query para turnos (según rol)
        $shiftQuery = Shift::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('employee_id', $employeeId))
            ->whereBetween('fecha_inicio', [$start, $end]);

        // 3. Métricas agrupadas (Agregaciones puras en DB)
        
        // Turnos Pendientes: Solo count()
        $pendingShifts = (clone $shiftQuery)
            ->where('status', Shift::STATUS_PENDING)
            ->count();

        // Turnos Aprobados y No Anulados: Base para cálculos financieros
        $approvedBaseQuery = (clone $shiftQuery)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false);

        // Horas Aprobadas: SUM() de columnas de cálculo
        $approvedHours = ShiftCalculation::whereIn('shift_id', $approvedBaseQuery->pluck('id'))
            ->selectRaw('SUM(horas_diurnas + horas_nocturnas + horas_extra_diurnas + horas_extra_nocturnas) as total')
            ->value('total') ?? 0;

        // Pago Estimado: SUM() de valor_total
        $estimatedPay = ShiftCalculation::whereIn('shift_id', $approvedBaseQuery->pluck('id'))
            ->sum('valor_total') ?? 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'approved_hours' => (float) $approvedHours,
                'pending_shifts' => (int) $pendingShifts,
                'estimated_pay' => (float) $estimatedPay,
            ],
            'role' => $user->role,
        ]);
    }
}
