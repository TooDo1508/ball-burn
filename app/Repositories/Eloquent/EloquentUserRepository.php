<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository implements UserRepository
{
    public function saveProfile(User $user, array $attributes): User
    {
        $user->fill($attributes)->save();

        return $user;
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->forceFill(['password' => $password])->save();

        return $user;
    }
}
