<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractWebController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contract::class);

        $query = Contract::with('employee:id,nombre,documento')
            ->orderByDesc('fecha_inicio');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        return Inertia::render('Contracts/Index', [
            'contracts' => $query->paginate(10)->withQueryString(),
            'employees' => Employee::query()->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['employee_id', 'estado']),
            'estados' => [Contract::ESTADO_ACTIVO, Contract::ESTADO_INACTIVO],
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        Contract::create($request->validated());

        return redirect()
            ->route('contracts.index', $request->only(['employee_id', 'estado']))
            ->with('success', 'Contrato creado correctamente.');
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $contract->update($request->validated());

        return redirect()
            ->route('contracts.index', $request->only(['employee_id', 'estado']))
            ->with('success', 'Contrato actualizado correctamente.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        $contract->delete();

        return redirect()
            ->route('contracts.index')
            ->with('success', 'Contrato eliminado correctamente.');
    }
}
