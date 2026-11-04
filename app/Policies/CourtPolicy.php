<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Court $court): bool
    {
        return $user->canAccessAdmin();
    }
}
