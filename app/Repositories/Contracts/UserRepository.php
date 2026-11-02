<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    public function saveProfile(User $user, array $attributes): User;

    public function updatePassword(User $user, string $password): User;
}
