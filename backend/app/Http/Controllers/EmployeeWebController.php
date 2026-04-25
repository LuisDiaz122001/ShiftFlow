<?php

namespace App\Http\Controllers;

use App\Actions\CreateEmployeeAction;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeWebController extends Controller
{
    /**
     * Renderiza la interfaz administrativa de empleados (Vista).
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Employees/Index');
    }

    /**
     * Retorna el listado de empleados paginado en formato JSON (Datos).
     */
    public function data(): JsonResponse
    {
        // Usamos paginación para mejorar el rendimiento en SaaS
        $employees = Employee::with('user')
            ->orderBy('nombre')
            ->paginate(10);

        // Retornamos estructura estándar de Laravel Pagination para compatibilidad con el frontend
        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'total'        => $employees->total(),
                'from'         => $employees->firstItem(),
                'to'           => $employees->lastItem(),
            ],
        ]);
    }

    /**
     * Procesa la creación de un nuevo empleado vía Axios (JSON).
     */
    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $createEmployee): JsonResponse
    {
        try {
            $employee = $createEmployee->execute($request->validated());
            
            return response()->json([
                'message' => 'Empleado registrado correctamente.',
                'employee' => $employee
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el empleado: ' . $e->getMessage()
            ], 500);
        }
    }
}
