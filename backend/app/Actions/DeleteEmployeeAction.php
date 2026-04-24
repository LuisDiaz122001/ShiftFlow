<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteEmployeeAction
{
    public function execute(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            /** @var Employee $employee */
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->user_id) {
                $employee->delete();

                return;
            }

            /** @var User|null $user */
            $user = User::query()
                ->lockForUpdate()
                ->find($employee->user_id);

            if ($user) {
                $user->delete();

                return;
            }

            $employee->delete();
        });
    }
}
