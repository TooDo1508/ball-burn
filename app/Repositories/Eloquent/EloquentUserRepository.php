<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    private function managementQuery(): Builder
    {
        return User::query()->whereIn(DB::raw('UPPER(role)'), array_keys(User::ROLES));
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->managementQuery()
            ->select(['id', 'name', 'email', 'phone', 'role', 'is_active', 'account_status', 'created_at'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereRaw('UPPER(role) = ?', [$role]))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('account_status', $status))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function managementStats(): array
    {
        $counts = $this->managementQuery()->selectRaw('account_status, COUNT(*) AS total')->groupBy('account_status')->pluck('total', 'account_status');

        return ['total' => (int) $counts->sum(), 'active' => (int) ($counts['ACTIVE'] ?? 0), 'locked' => (int) ($counts['LOCKED'] ?? 0), 'disabled' => (int) ($counts['DISABLED'] ?? 0)];
    }

    public function lockForManagement(int $actorId, ?int $targetId): Collection
    {
        // Lock in a common order, including all admins, to protect the final active admin.
        return User::query()->where(fn ($query) => $query->whereIn('id', array_filter([$actorId, $targetId]))
            ->orWhereRaw('UPPER(role) = ?', ['ADMIN']))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    public function saveManagedUser(?User $user, array $attributes): User
    {
        $user ??= new User;
        if ($user->exists && $user->email !== $attributes['email']) {
            $user->email_verified_at = null;
        }
        $user->forceFill($attributes)->save();

        return $user;
    }

    public function deleteManagedUser(User $user): void
    {
        $user->delete();
    }
}
