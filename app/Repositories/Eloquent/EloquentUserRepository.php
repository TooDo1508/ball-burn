<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

    public function paginateForAdmin(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->whereIn(DB::raw('UPPER(role)'), ['ADMIN', 'STAFF'])
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
