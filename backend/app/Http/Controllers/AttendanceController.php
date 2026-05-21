<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Muestra la vista de asistencia del usuario autenticado.
     */
    public function index(): Response|RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('shifts.index')
                ->withErrors(['attendance' => 'Su cuenta no tiene perfil de empleado asociado.']);
        }

        $attendances = $employee->attendances()
            ->orderByDesc('check_in')
            ->get();

        $activeAttendance = $employee->attendances()
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'activeAttendance' => $activeAttendance,
        ]);
    }

    /**
     * Registra el check-in del usuario autenticado.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return back()->withErrors(['attendance' => 'El usuario no tiene perfil de empleado asociado.']);
        }

        $openAttendance = Attendance::where('employee_id', $employee->id)
            ->whereNull('check_out')
            ->latest()
            ->first();

        if ($openAttendance) {
            return back()->withErrors([
                'attendance' => 'Ya existe un registro de asistencia abierto. Debe hacer check-out primero.',
            ]);
        }

        try {
            Attendance::create([
                'employee_id' => $employee->id,
                'check_in' => now(),
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['attendance' => 'Error al registrar check-in: '.$e->getMessage()]);
        }

        return back()->with('success', 'Check-in registrado exitosamente.');
    }

    /**
     * Registra el check-out y calcula las horas trabajadas.
     */
    public function checkOut(Request $request, Attendance $attendance): RedirectResponse
    {
        $employee = $request->user()->employee;

        if (! $employee || $attendance->employee_id !== $employee->id) {
            return back()->withErrors(['attendance' => 'No autorizado para actualizar este registro.']);
        }

        if (! $attendance->check_in) {
            return back()->withErrors(['attendance' => 'El registro no tiene check-in registrado.']);
        }

        if ($attendance->check_out) {
            return back()->withErrors(['attendance' => 'Este registro ya tiene check-out registrado.']);
        }

        try {
            $checkIn = Carbon::parse($attendance->check_in);
            $checkOut = now();
            $totalHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);

            $attendance->update([
                'check_out' => $checkOut,
                'total_hours' => $totalHours,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['attendance' => 'Error al registrar check-out: '.$e->getMessage()]);
        }

        return back()->with('success', 'Check-out registrado exitosamente.');
    }
}
