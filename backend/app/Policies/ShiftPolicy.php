<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShiftPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    public function view(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->employee && $shift->employee_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return true; 
    }

    public function update(User $user, Shift $shift): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $shift->status === Shift::STATUS_PENDING;
    }

    public function delete(User $user, Shift $shift): bool
    {
        return false; // Zero-Delete Policy
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    public function void(User $user, Shift $shift): bool
    {
        return $user->isAdmin() && $shift->status === Shift::STATUS_APPROVED;
    }
}
