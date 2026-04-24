<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UpdateEmployeeAction
{
    public function execute(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            /** @var Employee $employee */
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->user_id) {
                throw new RuntimeException('El empleado no tiene un usuario asociado y no puede actualizarse por API.');
            }

            /** @var User $user */
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($employee->user_id);

            $employeeData = Arr::only($data, [
                'nombre',
                'documento',
                'telefono',
                'salario_base',
                'activo',
            ]);

            if (array_key_exists('salario_base', $employeeData)) {
                $employeeData['salario_base'] = round((float) $employeeData['salario_base'], 2);
            }

            $employee->fill($employeeData);
            $employee->save();

            $userData = [];

            if (array_key_exists('nombre', $data)) {
                $userData['name'] = $data['nombre'];
            }

            if (array_key_exists('email', $data)) {
                $userData['email'] = $data['email'];
            }

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if ($userData !== []) {
                $user->fill($userData);
                $user->save();
            }

            return $employee->load('user');
        });
    }
}
