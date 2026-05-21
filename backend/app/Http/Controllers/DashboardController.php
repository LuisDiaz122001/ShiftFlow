<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $shiftScope = Shift::query()
            ->when(! $isAdminOrSupervisor, fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->when($selectedEmployeeId, fn (Builder $query, $value) => $query->where('employee_id', $value));

        $rangeScope = (clone $shiftScope)->whereBetween('fecha_inicio', [
            "{$dateFrom} 00:00:00",
            "{$dateTo} 23:59:59",
        ]);

        $aggregates = (clone $rangeScope)
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

        $hoursToday = (float) (clone $shiftScope)
            ->whereDate('fecha_inicio', today())
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->sum('total_hours');

        $hoursWeek = (float) (clone $shiftScope)
            ->whereBetween('fecha_inicio', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->sum('total_hours');

        $hoursMonth = (float) (clone $shiftScope)
            ->whereBetween('fecha_inicio', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->sum('total_hours');

        $hoursSeries = $this->buildHoursPerDaySeries($rangeScope, $dateFrom, $dateTo);

        $payrollScope = Payroll::query()
            ->when(! $isAdminOrSupervisor, fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->when($selectedEmployeeId, fn (Builder $query, $value) => $query->where('employee_id', $value))
            ->whereBetween('fecha_pago', [$dateFrom, $dateTo]);

        $payrollAccumulated = (float) (clone $payrollScope)
            ->whereIn('estado', [Payroll::STATUS_PENDING, Payroll::STATUS_PAID])
            ->sum('total_amount');

        $payrollPaidCount = (int) (clone $payrollScope)->where('estado', Payroll::STATUS_PAID)->count();

        $payrollTrendSeries = $this->buildPayrollTrendSeries($payrollScope);

        $attendanceScope = Attendance::query()
            ->when(! $isAdminOrSupervisor, fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->when($selectedEmployeeId, fn (Builder $query, $value) => $query->where('employee_id', $value));

        $attendanceToday = (int) (clone $attendanceScope)->whereDate('check_in', today())->count();
        $attendanceOpen = (int) (clone $attendanceScope)->whereNull('check_out')->count();

        $activeEmployees = $isAdminOrSupervisor
            ? Employee::query()->where('activo', true)->count()
            : ($employeeId ? 1 : 0);

        $topEmployees = (clone $rangeScope)
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

        $employees = $isAdminOrSupervisor
            ? Employee::query()
                ->with('user:id,name')
                ->orderBy('nombre')
                ->get(['id', 'user_id', 'nombre'])
            : [];

        if (app()->environment(['local', 'testing'])) {
            Log::debug('Dashboard data flow', [
                'user_id' => $user->id,
                'role' => $user->role,
                'filters' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'employee_id' => $selectedEmployeeId,
                ],
                'hours_series_raw' => $hoursSeries['raw_count'],
                'hours_series_points' => $hoursSeries['series']->count(),
                'payroll_series_raw' => $payrollTrendSeries['raw_count'],
                'payroll_series_points' => $payrollTrendSeries['series']->count(),
                'hours_series_preview' => $hoursSeries['series']->take(5)->values()->all(),
                'payroll_series_preview' => $payrollTrendSeries['series']->take(5)->values()->all(),
            ]);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_hours_worked' => round((float) ($aggregates->total_hours_worked ?? 0), 2),
                'total_paid' => round((float) ($aggregates->total_paid ?? 0), 2),
                'pending_count' => (int) ($aggregates->pending_count ?? 0),
                'approved_count' => (int) ($aggregates->approved_count ?? 0),
                'void_count' => (int) ($aggregates->void_count ?? 0),
                'hours_today' => round($hoursToday, 2),
                'hours_week' => round($hoursWeek, 2),
                'hours_month' => round($hoursMonth, 2),
                'active_employees' => (int) $activeEmployees,
                'attendance_today' => $attendanceToday,
                'attendance_open' => $attendanceOpen,
                'payroll_accumulated' => round($payrollAccumulated, 2),
                'payroll_paid_count' => $payrollPaidCount,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_id' => $selectedEmployeeId,
            ],
            'employees' => $employees,
            'employeesCount' => (int) ($isAdminOrSupervisor ? Employee::count() : ($employeeId ? 1 : 0)),
            'topEmployees' => $topEmployees,
            'hoursPerDay' => $hoursSeries['series'],
            'payrollTrend' => $payrollTrendSeries['series'],
            'role' => $user->role,
        ]);
    }

    /**
     * @return array{raw_count:int, series: Collection<int, array{date: string, total_hours: float}>}
     */
    private function buildHoursPerDaySeries(Builder $scope, string $dateFrom, string $dateTo): array
    {
        $rawSeries = (clone $scope)
            ->whereBetween('fecha_inicio', [
                "{$dateFrom} 00:00:00",
                "{$dateTo} 23:59:59",
            ])
            ->where('status', Shift::STATUS_APPROVED)
            ->where('is_voided', false)
            ->selectRaw('DATE(fecha_inicio) as date')
            ->selectRaw('ROUND(SUM(total_hours), 2) as total_hours')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        if ($rawSeries->isEmpty()) {
            return [
                'raw_count' => 0,
                'series' => collect(),
            ];
        }

        $start = Carbon::parse($dateFrom, config('app.timezone'))->startOfDay();
        $end = Carbon::parse($dateTo, config('app.timezone'))->startOfDay();

        $series = collect(CarbonPeriod::create($start, $end))->map(static function (Carbon $day) use ($rawSeries) {
            $date = $day->toDateString();

            return [
                'date' => $date,
                'total_hours' => (float) ($rawSeries->get($date)->total_hours ?? 0),
            ];
        });

        return [
            'raw_count' => $rawSeries->count(),
            'series' => $series,
        ];
    }

    /**
     * @return array{raw_count:int, series: Collection<int, array{month: string, total: float}>}
     */
    private function buildPayrollTrendSeries(Builder $scope): array
    {
        $start = now()->subMonths(5)->startOfMonth()->startOfDay();
        $end = now()->endOfMonth()->endOfDay();
        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', fecha_pago)"
            : "DATE_FORMAT(fecha_pago, '%Y-%m')";

        $rawSeries = (clone $scope)
            ->whereIn('estado', [Payroll::STATUS_PENDING, Payroll::STATUS_PAID])
            ->whereBetween('fecha_pago', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw('ROUND(SUM(total_amount), 2) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        if ($rawSeries->isEmpty()) {
            return [
                'raw_count' => 0,
                'series' => collect(),
            ];
        }

        $months = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonthNoOverflow();
        }

        $series = collect($months)->map(static function (string $month) use ($rawSeries) {
            return [
                'month' => $month,
                'total' => (float) ($rawSeries->get($month)->total ?? 0),
            ];
        });

        return [
            'raw_count' => $rawSeries->count(),
            'series' => $series,
        ];
    }
}
