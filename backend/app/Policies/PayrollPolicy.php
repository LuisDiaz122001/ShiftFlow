<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PayrollPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->employee && $payroll->employee_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return false; // Nóminas son inmutables una vez generadas (se regeneran, no se editan)
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->isAdmin();
    }
}
