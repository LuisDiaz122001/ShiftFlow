<?php

namespace App\Policies;

use App\Models\LaborRule;
use App\Models\User;

class LaborRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, LaborRule $laborRule): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, LaborRule $laborRule): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, LaborRule $laborRule): bool
    {
        return $user->isAdmin();
    }
}
