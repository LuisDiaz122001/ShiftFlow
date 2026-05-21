<?php

namespace App\Http\Controllers;

use App\Actions\CreateEmployeeAction;
use App\Actions\DeleteEmployeeAction;
use App\Actions\UpdateEmployeeAction;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeWebController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Employee::with('user')
            ->orderBy('nombre');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('documento', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('Employees/Index', [
            'employees' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search'),
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
            ->route('employees.index', $request->only('search'))
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
