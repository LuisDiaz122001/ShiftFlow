<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeWebController;
use App\Http\Controllers\PayrollAuditController;
use App\Http\Controllers\PayrollCycleController;
use App\Http\Controllers\PayrollWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShiftWebController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', function () {
    return 'OK LARAVEL FUNCIONANDO';
});

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:admin,supervisor'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin,supervisor'])->group(function () {
    Route::get('/employees', [EmployeeWebController::class, 'index'])->name('employees.index');
    Route::get('/employees/data', [EmployeeWebController::class, 'data'])->name('employees.data');
    Route::post('/employees', [EmployeeWebController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeWebController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeWebController::class, 'destroy'])->name('employees.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/shifts', [ShiftWebController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [ShiftWebController::class, 'store'])->name('shifts.store');

    // Rutas de Asistencia
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkIn');
    Route::post('/attendance/{attendance}/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkOut');
});

Route::middleware(['auth', 'role:admin,supervisor'])->group(function () {
    Route::get('/payrolls', [PayrollWebController::class, 'index'])->name('payrolls.index');
    Route::get('/payrolls/dashboard', [PayrollWebController::class, 'dashboard'])->name('payrolls.dashboard');
    Route::get('/payrolls/financial', [PayrollWebController::class, 'financialSummary'])->name('payrolls.financial');

    // Auditoría y Gestión de Periodos (Solo Admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/payrolls/audit', [PayrollAuditController::class, 'index'])->name('payrolls.audit');
        Route::get('/payrolls/periods', [PayrollCycleController::class, 'index'])->name('payrolls.periods');
        Route::post('/payrolls/periods/{cycle}/close', [PayrollCycleController::class, 'close'])->name('payrolls.periods.close');
    });

    Route::post('/payrolls', [PayrollWebController::class, 'store'])->name('payrolls.store');
    Route::post('/payrolls/bulk', [PayrollWebController::class, 'bulkStore'])->name('payrolls.bulkStore');
    Route::post('/payrolls/bulk-pay', [PayrollWebController::class, 'bulkPay'])->name('payrolls.bulkPay');
    Route::get('/payrolls/{payroll}', [PayrollWebController::class, 'show'])
        ->whereNumber('payroll')
        ->name('payrolls.show');
    Route::get('/payrolls/{payroll}/export', [PayrollWebController::class, 'exportPdf'])
        ->whereNumber('payroll')
        ->name('payrolls.export');
    Route::get('/payrolls/{payroll}/pdf', [PayrollWebController::class, 'exportPdf'])
        ->whereNumber('payroll')
        ->name('payrolls.pdf');
    Route::patch('/payrolls/{payroll}/status', [PayrollWebController::class, 'updateStatus'])
        ->whereNumber('payroll')
        ->name('payrolls.updateStatus');
    Route::patch('/payrolls/{payroll}/pay', [PayrollWebController::class, 'markAsPaid'])
        ->whereNumber('payroll')
        ->name('payrolls.pay');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/contact', function () {
    return Inertia::render('ContactPage');
})->name('contact');

Route::get('/terms', function () {
    return Inertia::render('TermsPage');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('PrivacyPage');
})->name('privacy');

require __DIR__.'/auth.php';
