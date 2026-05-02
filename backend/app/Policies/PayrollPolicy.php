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
        return $user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);
    }

    public function updateAny(User $user): bool
    {
        return $user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR])) {
            return true;
        }

        // Los empleados solo pueden ver su propia nómina
        return $user->employee && $payroll->employee_id === $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        // Las nóminas son inmutables en sus datos financieros (Model hooks lo refuerzan)
        // Solo administradores pueden cambiar estados (pay/cancel)
        return $user->isAdmin() && $payroll->estado !== Payroll::STATUS_PAID;
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        // Solo administradores pueden borrar y solo si no está pagada
        return $user->isAdmin() && $payroll->estado === Payroll::STATUS_PENDING;
    }
}
