<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Muestra la vista de asistencia del usuario autenticado.
     */
    public function index()
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return redirect()->route('dashboard');
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
    public function checkIn(Request $request)
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene perfil de empleado asociado.',
            ], 400);
        }

        // Verificar si ya existe un check-in abierto (sin check_out)
        $openAttendance = Attendance::where('employee_id', $employee->id)
            ->whereNull('check_out')
            ->latest()
            ->first();

        if ($openAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un registro de asistencia abierto. Debe hacer check-out primero.',
                'data' => $openAttendance,
            ], 409);
        }

        // Crear nuevo registro de asistencia
        try {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'check_in' => now(),
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in registrado exitosamente.',
                'data' => $attendance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar check-in: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registra el check-out y calcula las horas trabajadas.
     */
    public function checkOut(Attendance $attendance)
    {
        // Validar que el registro pertenezca al usuario autenticado
        $employee = auth()->user()->employee;

        if (!$employee || $attendance->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para actualizar este registro.',
            ], 403);
        }

        // Validar que existe check_in
        if (!$attendance->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'El registro no tiene check-in registrado.',
            ], 400);
        }

        // Validar que no ya existe check_out
        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'Este registro ya tiene check-out registrado.',
            ], 409);
        }

        try {
            $checkIn = Carbon::parse($attendance->check_in);
            $checkOut = now();

            // Calcular horas trabajadas
            $totalHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);

            // Actualizar registro
            $attendance->update([
                'check_out' => $checkOut,
                'total_hours' => $totalHours,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out registrado exitosamente.',
                'data' => [
                    'attendance' => $attendance->refresh(),
                    'total_hours' => $totalHours,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar check-out: ' . $e->getMessage(),
            ], 500);
        }
    }
}

