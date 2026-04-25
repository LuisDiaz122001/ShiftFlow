<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\User;

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

        // 1. Determinar nivel de acceso (Admin o Supervisor)
        $isAdminOrSupervisor = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);

        // 2. Obtener ID de empleado si aplica (Blindaje de Ownership)
        $employeeId = $user->employee?->id;

        // 3. Base query para turnos (según rol)
        $shiftQuery = Shift::query()
            ->when(!$isAdminOrSupervisor, fn($q) => $q->where('employee_id', $employeeId))
            ->whereBetween('fecha_inicio', [$start, $end]);

        // 4. Métricas agrupadas (Agregaciones puras en DB)
        
        // Turnos Pendientes: Solo count()
        $pendingShifts = (clone $shiftQuery)
            ->where('status', Shift::STATUS_PENDING)
            ->count();

        // Turnos Aprobados y No Anulados: Base para cálculos financieros
        $approvedBaseQuery = (clone $shiftQuery)
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false);

        // Agregaciones directas desde la tabla shifts (Source of Truth)
        $metrics = $approvedBaseQuery->selectRaw('
            SUM(total_hours) as approved_hours,
            SUM(total_pago) as estimated_pay
        ')->first();

        // 5. Conteo de Empleados (Lógica solicitada por el usuario)
        $employeesCount = $isAdminOrSupervisor
            ? Employee::count() 
            : ($employeeId ? 1 : 0);

        return Inertia::render('Dashboard', [
            'stats' => [
                'approved_hours' => (float) ($metrics->approved_hours ?? 0),
                'pending_shifts' => (int) $pendingShifts,
                'estimated_pay' => (float) ($metrics->estimated_pay ?? 0),
            ],
            'employeesCount' => (int) $employeesCount,
            'role' => $user->role,
        ]);
    }
}
