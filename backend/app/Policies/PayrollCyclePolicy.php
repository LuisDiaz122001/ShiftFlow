<?php

namespace App\Policies;

use App\Models\PayrollCycle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PayrollCyclePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    public function view(User $user, PayrollCycle $payrollCycle): bool
    {
        return true; 
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PayrollCycle $payrollCycle): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PayrollCycle $payrollCycle): bool
    {
        return $user->isAdmin() && $payrollCycle->estado === PayrollCycle::STATUS_OPEN;
    }

    public function process(User $user): bool
    {
        return $user->isAdmin();
    }

    public function close(User $user): bool
    {
        return $user->isAdmin();
    }
}
