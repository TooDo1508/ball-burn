<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $actor): bool
    {
        return $actor->canAccessAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->canAccessAdmin() && $user->roleLevel() > 0 && $actor->roleLevel() >= $user->roleLevel();
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->id !== $user->id && $this->update($actor, $user);
    }
}
