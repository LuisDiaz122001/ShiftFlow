<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class EmployeeWebController extends Controller
{
    /**
     * Renderiza la interfaz administrativa de empleados.
     */
    public function index(): Response
    {
        return Inertia::render('Employees/Index');
    }
}
