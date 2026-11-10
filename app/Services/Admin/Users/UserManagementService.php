<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(private readonly UserRepository $users) {}

    public function save(User $actor, ?User $target, array $data): User
    {
        return DB::transaction(function () use ($actor, $target, $data): User {
            $locked = $this->users->lockForManagement($actor->id, $target?->id);
            $actor = $locked->get($actor->id);
            abort_unless($actor?->canAccessAdmin(), 403);
            if ($target) {
                $target = $locked->get($target->id);
                abort_unless($target, 404);
                Gate::forUser($actor)->authorize('update', $target);
            } else {
                Gate::forUser($actor)->authorize('create', User::class);
            }
            abort_unless((User::ROLES[$data['role']] ?? PHP_INT_MAX) <= $actor->roleLevel(), 403);
            if ($target?->id === $actor->id && ($data['account_status'] !== 'ACTIVE' || $data['role'] !== strtoupper($actor->role))) {
                throw ValidationException::withMessages(['account_status' => 'Bạn không thể tự khóa hoặc thay đổi vai trò của mình.']);
            }
            if ($target && strtoupper($target->role) === 'ADMIN' && $target->canAccessAdmin()
                && ($data['role'] !== 'ADMIN' || $data['account_status'] !== 'ACTIVE')
                && $locked->filter(fn (User $user) => strtoupper($user->role) === 'ADMIN' && $user->canAccessAdmin())->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Cần giữ ít nhất một Admin đang hoạt động.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
            unset($data['password_confirmation']);
            $data['is_active'] = $data['account_status'] === 'ACTIVE';

            return $this->users->saveManagedUser($target, $data);
        });
    }

    public function delete(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $locked = $this->users->lockForManagement($actor->id, $target->id);
            $actor = $locked->get($actor->id);
            $target = $locked->get($target->id);
            abort_unless($actor?->canAccessAdmin(), 403);
            abort_unless($target, 404);
            Gate::forUser($actor)->authorize('delete', $target);
            if (strtoupper($target->role) === 'ADMIN' && $target->canAccessAdmin()
                && $locked->filter(fn (User $user) => strtoupper($user->role) === 'ADMIN' && $user->canAccessAdmin())->count() <= 1) {
                throw ValidationException::withMessages(['user' => 'Không thể xóa Admin đang hoạt động cuối cùng.']);
            }
            $this->users->deleteManagedUser($target);
        });
    }
}
