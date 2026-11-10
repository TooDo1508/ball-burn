<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepository
{
    public function saveProfile(User $user, array $attributes): User;

    public function updatePassword(User $user, string $password): User;

    public function paginateForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function managementStats(): array;

    public function lockForManagement(int $actorId, ?int $targetId): Collection;

    public function saveManagedUser(?User $user, array $attributes): User;

    public function deleteManagedUser(User $user): void;
}
