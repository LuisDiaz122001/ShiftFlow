<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PayrollCycleController;
use App\Http\Controllers\Api\V1\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('api.')->group(function () {

    // ─────────────────────────────────────────────────
    // PÚBLICA — No requiere autenticación
    // ─────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login']);

    // ─────────────────────────────────────────────────
    // PROTEGIDA — Requiere token Sanctum
    // ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth / Perfil propio (todos los roles)
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // ─────────────────────────────────────────────
        // EMPLOYEE — Lectura de sus propios recursos
        // ─────────────────────────────────────────────

        // Turnos: cualquier autenticado puede registrar (pending) y ver los suyos
        Route::apiResource('shifts', ShiftController::class)->only(['index', 'store']);

        // Nóminas: lectura propia (PayrollPolicy filtra por owner)
        Route::apiResource('payrolls', PayrollController::class)->only(['index', 'show']);

        // ─────────────────────────────────────────────
        // ADMIN + SUPERVISOR — Gestión de empleados
        // ─────────────────────────────────────────────
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::apiResource('employees', EmployeeController::class)->only([
                'index',
                'show',
                'store',
                'update',
                'destroy',
            ]);
        });

        // ─────────────────────────────────────────────
        // ADMIN ONLY — Operaciones de auditoría y nómina
        // ─────────────────────────────────────────────
        Route::middleware('role:admin')->group(function () {
            // Acciones de auditoría de turnos
            Route::post('shifts/{shift}/approve', [ShiftController::class, 'approve']);
            Route::post('shifts/{shift}/reject', [ShiftController::class, 'reject']);
            Route::post('shifts/{shift}/void', [ShiftController::class, 'void']);

            // Ciclos de nómina (creación, procesado y cierre)
            Route::apiResource('payroll-cycles', PayrollCycleController::class)->only(['store', 'show']);
            Route::post('payroll-cycles/{payroll_cycle}/process', [PayrollCycleController::class, 'process']);
            Route::post('payroll-cycles/{payroll_cycle}/close', [PayrollCycleController::class, 'close']);
        });
    });
});
