<?php

namespace App\Http\Controllers;

use App\Actions\CreateEmployeeAction;
use App\Actions\DeleteEmployeeAction;
use App\Actions\UpdateEmployeeAction;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeWebController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Employees/Index');
    }

    public function data(): JsonResponse
    {
        $employees = Employee::with('user')
            ->orderBy('nombre')
            ->paginate(10);

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $createEmployee): RedirectResponse
    {
        $createEmployee->execute($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployeeAction $updateEmployee): RedirectResponse
    {
        $updateEmployee->execute($employee, $request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Employee $employee, DeleteEmployeeAction $deleteEmployee): RedirectResponse
    {
        $deleteEmployee->execute($employee);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Empleado eliminado correctamente.');
    }
}
