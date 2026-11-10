<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepository
{
    public function saveProfile(User $user, array $attributes): User;

    public function updatePassword(User $user, string $password): User;

    public function paginateForAdmin(?string $search = null, int $perPage = 20): LengthAwarePaginator;
}
