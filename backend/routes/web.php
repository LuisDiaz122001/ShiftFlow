<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeWebController;
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
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:admin,supervisor'])
    ->name('dashboard');

// Rutas exclusivas de Admin y Supervisor
Route::middleware(['auth', 'role:admin,supervisor'])->group(function () {
    // Vista (UI)
    Route::get('/employees', [EmployeeWebController::class, 'index'])->name('employees.index');
    
    // Datos (JSON para Axios)
    Route::get('/employees/data', [EmployeeWebController::class, 'data'])->name('employees.data');
    Route::post('/employees', [EmployeeWebController::class, 'store'])->name('employees.store');
});

// Turnos — accesible a todos los roles autenticados (el controlador filtra por el perfil)
Route::middleware('auth')->group(function () {
    Route::get('/shifts', [ShiftWebController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [ShiftWebController::class, 'store'])->name('shifts.store');
    
    // Nómina
    Route::get('/payrolls', [PayrollWebController::class, 'index'])->name('payrolls.index');
    Route::post('/payrolls', [PayrollWebController::class, 'store'])->name('payrolls.store');
    Route::get('/payrolls/{payroll}', [PayrollWebController::class, 'show'])->name('payrolls.show');
    Route::get('/payrolls/{id}/export', [PayrollWebController::class, 'exportPdf'])->name('payrolls.export');
    Route::patch('/payrolls/{payroll}/pay', [PayrollWebController::class, 'markAsPaid'])->name('payrolls.pay');
});

// Rutas comunes a todos los usuarios autenticados (perfil propio)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Páginas Públicas Informativas
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
