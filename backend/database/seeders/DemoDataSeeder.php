<?php

namespace Database\Seeders;

use App\Actions\CalculateShiftAction;
use App\Actions\GeneratePayrollAction;
use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LaborRule;
use App\Models\Payroll;
use App\Models\PayrollCycle;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Iniciando DemoDataSeeder...');

        $admin = $this->seedUsers();
        $this->seedLaborRules();
        $this->seedHolidays();
        $employees = $this->seedEmployees($admin);
        $cycles = $this->seedPayrollCycles();
        $this->seedShifts($employees, $admin, $cycles);
        $this->seedAttendances($employees);
        $this->seedPayrolls($cycles);

        $this->command?->info('DemoDataSeeder completado.');
        $this->command?->table(
            ['Entidad', 'Cantidad'],
            [
                ['Usuarios', User::count()],
                ['Empleados', Employee::count()],
                ['Contratos', Contract::count()],
                ['Turnos', Shift::count()],
                ['Asistencias', Attendance::count()],
                ['Periodos', PayrollCycle::count()],
                ['Nóminas', Payroll::count()],
                ['Festivos', Holiday::count()],
            ]
        );
    }

    private function seedUsers(): User
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin ShiftFlow',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@test.com'],
            [
                'name' => 'Supervisor Operaciones',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPERVISOR,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'emp@test.com'],
            [
                'name' => 'Empleado Prueba',
                'password' => Hash::make('password'),
                'role' => User::ROLE_EMPLOYEE,
                'email_verified_at' => now(),
            ]
        );

        return $admin;
    }

    private function seedLaborRules(): void
    {
        LaborRule::query()->updateOrCreate(
            ['vigente_desde' => now()->subYear()->startOfYear()->toDateString()],
            [
                'hora_diurna_inicio' => '06:00:00',
                'hora_nocturna_inicio' => '21:00:00',
                'recargo_nocturno' => 35.00,
                'recargo_dominical' => 75.00,
                'extra_diurna' => 25.00,
                'extra_nocturna' => 75.00,
                'porcentaje_salud' => 4.00,
                'porcentaje_pension' => 4.00,
                'horas_max_diarias' => 8.00,
            ]
        );
    }

    private function seedHolidays(): void
    {
        $year = now()->year;
        $holidays = [
            ['fecha' => "{$year}-01-01", 'nombre' => 'Año Nuevo'],
            ['fecha' => "{$year}-05-01", 'nombre' => 'Día del Trabajo'],
            ['fecha' => "{$year}-07-20", 'nombre' => 'Independencia de Colombia'],
            ['fecha' => "{$year}-08-07", 'nombre' => 'Batalla de Boyacá'],
            ['fecha' => "{$year}-12-25", 'nombre' => 'Navidad'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::query()->firstOrCreate(
                ['fecha' => $holiday['fecha']],
                ['nombre' => $holiday['nombre']]
            );
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    private function seedEmployees(User $admin): \Illuminate\Support\Collection
    {
        $employees = collect();

        $demoProfiles = [
            ['nombre' => 'Empleado Prueba', 'email' => 'emp@test.com', 'documento' => 'CC1000000001', 'salario' => 2500000],
            ['nombre' => 'Ana María López', 'email' => 'empleado1@test.com', 'documento' => 'CC1000000002', 'salario' => 2800000],
            ['nombre' => 'Carlos Ruiz', 'email' => 'empleado2@test.com', 'documento' => 'CC1000000003', 'salario' => 3100000],
            ['nombre' => 'Diana Torres', 'email' => 'empleado3@test.com', 'documento' => 'CC1000000004', 'salario' => 2650000],
            ['nombre' => 'Esteban Vargas', 'email' => 'empleado4@test.com', 'documento' => 'CC1000000005', 'salario' => 3200000],
            ['nombre' => 'Felicia Mendoza', 'email' => 'empleado5@test.com', 'documento' => 'CC1000000006', 'salario' => 2400000],
            ['nombre' => 'Gabriel Soto', 'email' => 'empleado6@test.com', 'documento' => 'CC1000000007', 'salario' => 2900000],
            ['nombre' => 'Helena Castro', 'email' => 'empleado7@test.com', 'documento' => 'CC1000000008', 'salario' => 2750000],
            ['nombre' => 'Iván Herrera', 'email' => 'empleado8@test.com', 'documento' => 'CC1000000009', 'salario' => 3000000],
        ];

        foreach ($demoProfiles as $index => $profile) {
            $user = User::updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['nombre'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_EMPLOYEE,
                    'email_verified_at' => now(),
                ]
            );

            $employee = Employee::updateOrCreate(
                ['documento' => $profile['documento']],
                [
                    'user_id' => $user->id,
                    'nombre' => $profile['nombre'],
                    'telefono' => '300'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT),
                    'salario_base' => $profile['salario'],
                    'activo' => $index < 8,
                ]
            );

            Contract::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'fecha_inicio' => now()->subMonths(6)->startOfMonth()->toDateString(),
                ],
                [
                    'salario_base' => $profile['salario'],
                    'fecha_fin' => null,
                    'estado' => Contract::ESTADO_ACTIVO,
                ]
            );

            $employees->push($employee);
        }

        return $employees;
    }

    /**
     * @return \Illuminate\Support\Collection<int, PayrollCycle>
     */
    private function seedPayrollCycles(): \Illuminate\Support\Collection
    {
        $cycles = collect();

        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->subMonths($offset)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $cycle = PayrollCycle::query()->firstOrCreate(
                [
                    'fecha_inicio' => $start->toDateString(),
                    'fecha_fin' => $end->toDateString(),
                ],
                [
                    'fecha_pago' => $end->toDateString(),
                    'estado' => PayrollCycle::STATUS_OPEN,
                ]
            );

            $cycle->update([
                'estado' => PayrollCycle::STATUS_OPEN,
                'fecha_pago' => $end->toDateString(),
            ]);

            $cycles->push($cycle);
        }

        return $cycles;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @param  \Illuminate\Support\Collection<int, PayrollCycle>  $cycles
     */
    private function seedShifts(\Illuminate\Support\Collection $employees, User $admin, \Illuminate\Support\Collection $cycles): void
    {
        /** @var CalculateShiftAction $calculate */
        $calculate = app(CalculateShiftAction::class);

        $sampleDays = [3, 8, 12, 17, 22];

        foreach ($cycles as $cycle) {
            $cycleStart = Carbon::parse($cycle->fecha_inicio);
            $cycleEnd = Carbon::parse($cycle->fecha_fin);

            foreach ($employees->where('activo', true) as $employeeIndex => $employee) {
                foreach ($sampleDays as $dayOffset) {
                    $cursor = $cycleStart->copy()->addDays(min($dayOffset, $cycleStart->diffInDays($cycleEnd)));

                    if ($cursor->gt($cycleEnd) || $cursor->isWeekend() || $cursor->isFuture()) {
                        continue;
                    }

                    $status = ($employeeIndex + $dayOffset) % 6 === 0
                        ? Shift::STATUS_PENDING
                        : (($employeeIndex + $dayOffset) % 9 === 0 ? Shift::STATUS_REJECTED : Shift::STATUS_APPROVED);

                    $start = $cursor->copy()->setTime(8, 0);
                    $end = $cursor->copy()->setTime(16, 0);

                    $shift = Shift::query()->create([
                        'user_id' => $employee->user_id,
                        'employee_id' => $employee->id,
                        'payroll_cycle_id' => $cycle->id,
                        'fecha_inicio' => $start,
                        'fecha_fin' => $end,
                        'status' => $status,
                        'approved_by' => $status === Shift::STATUS_APPROVED ? $admin->id : null,
                        'approved_at' => $status === Shift::STATUS_APPROVED ? now() : null,
                        'rejected_by' => $status === Shift::STATUS_REJECTED ? $admin->id : null,
                        'rejected_at' => $status === Shift::STATUS_REJECTED ? now() : null,
                        'notas' => 'Turno demo',
                    ]);

                    if ($status === Shift::STATUS_APPROVED) {
                        try {
                            $calculate->execute($shift);
                        } catch (\Throwable $e) {
                            $this->command?->warn("Turno {$shift->id}: {$e->getMessage()}");
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     */
    private function seedAttendances(\Illuminate\Support\Collection $employees): void
    {
        foreach ($employees->where('activo', true) as $index => $employee) {
            for ($day = 5; $day >= 1; $day--) {
                $checkIn = now()->subDays($day)->setTime(8, 5);
                Attendance::query()->create([
                    'employee_id' => $employee->id,
                    'check_in' => $checkIn,
                    'check_out' => $checkIn->copy()->addHours(8),
                    'total_hours' => 8.00,
                    'status' => 'approved',
                ]);
            }

            if ($index < 3) {
                Attendance::query()->create([
                    'employee_id' => $employee->id,
                    'check_in' => now()->setTime(8, 0),
                    'check_out' => null,
                    'total_hours' => null,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PayrollCycle>  $cycles
     */
    private function seedPayrolls(\Illuminate\Support\Collection $cycles): void
    {
        /** @var GeneratePayrollAction $generatePayroll */
        $generatePayroll = app(GeneratePayrollAction::class);

        foreach ($cycles as $cycle) {
            $employees = Employee::query()
                ->where('activo', true)
                ->whereHas('shifts', function ($query) use ($cycle) {
                    $query->where('payroll_cycle_id', $cycle->id)
                        ->where('status', Shift::STATUS_APPROVED)
                        ->where('is_voided', false);
                })
                ->orderBy('id')
                ->get();

            foreach ($employees as $employee) {
                try {
                    $generatePayroll->execute($employee, $cycle, true);
                } catch (\Throwable $e) {
                    $this->command?->warn("Nómina demo {$employee->id} / ciclo {$cycle->id}: {$e->getMessage()}");
                }
            }
        }

        $paidPayrollIds = Payroll::query()
            ->whereIn('estado', [Payroll::STATUS_PENDING, Payroll::STATUS_PAID])
            ->orderBy('fecha_pago')
            ->limit(8)
            ->pluck('id');

        if ($paidPayrollIds->isNotEmpty()) {
            Payroll::query()
                ->whereIn('id', $paidPayrollIds)
                ->update([
                    'estado' => Payroll::STATUS_PAID,
                    'paid_at' => now(),
                ]);

            $this->command?->info('Nóminas demo distribuidas en varios meses y marcadas parcialmente como pagadas.');
        }
    }
}
