<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdminOrSupervisor = $user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);
        $employeeId = $user->employee?->id;

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth()->toDateString();
        $selectedEmployeeId = $isAdminOrSupervisor ? ($validated['employee_id'] ?? null) : $employeeId;

        $baseQuery = Shift::query()
            ->when(! $isAdminOrSupervisor, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($selectedEmployeeId, fn ($query, $value) => $query->where('employee_id', $value))
            ->whereBetween('fecha_inicio', [
                "{$dateFrom} 00:00:00",
                "{$dateTo} 23:59:59",
            ]);

        $aggregates = (clone $baseQuery)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN status = ? AND is_voided = 0 THEN total_hours ELSE 0 END), 0) as total_hours_worked,
                COALESCE(SUM(CASE WHEN status = ? AND is_voided = 0 THEN total_pago ELSE 0 END), 0) as total_paid,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = ? AND is_voided = 0 THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN is_voided = 1 THEN 1 ELSE 0 END) as void_count
            ', [
                Shift::STATUS_APPROVED,
                Shift::STATUS_APPROVED,
                Shift::STATUS_PENDING,
                Shift::STATUS_APPROVED,
            ])
            ->first();

        $topEmployees = (clone $baseQuery)
            ->join('employees', 'employees.id', '=', 'shifts.employee_id')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->where('shifts.status', Shift::STATUS_APPROVED)
            ->where('shifts.is_voided', false)
            ->groupBy('employees.id', 'employees.nombre', 'users.name')
            ->orderByDesc(DB::raw('SUM(shifts.total_hours)'))
            ->limit(5)
            ->get([
                'employees.id as employee_id',
                'employees.nombre',
                'users.name as user_name',
                DB::raw('ROUND(SUM(shifts.total_hours), 2) as total_hours'),
            ]);

        $employeesCount = $isAdminOrSupervisor
            ? Employee::count()
            : ($employeeId ? 1 : 0);

        $employees = $isAdminOrSupervisor
            ? Employee::query()
                ->with('user:id,name')
                ->orderBy('nombre')
                ->get(['id', 'user_id', 'nombre'])
            : [];

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_hours_worked' => round((float) ($aggregates->total_hours_worked ?? 0), 2),
                'total_paid' => round((float) ($aggregates->total_paid ?? 0), 2),
                'pending_count' => (int) ($aggregates->pending_count ?? 0),
                'approved_count' => (int) ($aggregates->approved_count ?? 0),
                'void_count' => (int) ($aggregates->void_count ?? 0),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_id' => $selectedEmployeeId,
            ],
            'employees' => $employees,
            'employeesCount' => (int) $employeesCount,
            'topEmployees' => $topEmployees,
            'role' => $user->role,
        ]);
    }
}
