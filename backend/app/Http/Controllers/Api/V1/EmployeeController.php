<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateEmployeeAction;
use App\Actions\DeleteEmployeeAction;
use App\Actions\UpdateEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Resources\V1\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::query()
            ->with('user')
            ->paginate();

        return EmployeeResource::collection($employees);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load('user'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $createEmployee): JsonResponse
    {
        $employee = $createEmployee->execute($request->validated());

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee,
        UpdateEmployeeAction $updateEmployee,
    ): EmployeeResource|JsonResponse {
        try {
            $employee = $updateEmployee->execute($employee, $request->validated());

            return new EmployeeResource($employee);
        } catch (RuntimeException $exception) {
            return response()->json([
                'errors' => [$exception->getMessage()],
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee, DeleteEmployeeAction $deleteEmployee): JsonResponse
    {
        $deleteEmployee->execute($employee);

        return response()->json(status: 204);
    }
}
