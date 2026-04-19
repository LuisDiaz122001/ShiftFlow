<?php

use App\Http\Controllers\DashboardController;
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
    // (Futuras rutas web de administración de empleados irán aquí)
});

// Turnos — accesible a todos los roles autenticados (el controlador filtra por el perfil)
Route::middleware('auth')->group(function () {
    Route::get('/shifts', [ShiftWebController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [ShiftWebController::class, 'store'])->name('shifts.store');
});

// Rutas comunes a todos los usuarios autenticados (perfil propio)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';