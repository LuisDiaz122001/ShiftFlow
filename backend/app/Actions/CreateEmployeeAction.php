<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeAction
{
    public function execute(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $user = User::query()->create([
                'name' => $data['nombre'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_EMPLOYEE,
            ]);

            $employee = Employee::query()->create([
                'user_id' => $user->id,
                'nombre' => $data['nombre'],
                'documento' => $data['documento'],
                'telefono' => $data['telefono'] ?? null,
                'salario_base' => round((float) $data['salario_base'], 2),
                'activo' => $data['activo'] ?? true,
            ]);

            return $employee->load('user');
        });
    }
}
