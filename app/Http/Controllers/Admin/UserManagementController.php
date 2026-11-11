<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAdminUsersRequest;
use App\Http\Requests\Admin\SaveAdminUserRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Admin\Users\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserRepository $users, private readonly UserManagementService $management) {}

    public function index(IndexAdminUsersRequest $request): Response
    {
        $filters = $request->validated();
        $actor = $request->user();

        return Inertia::render('admin/users/Index', [
            'users' => $this->users->paginateForAdmin($filters)->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => strtoupper((string) $user->role),
                'account_status' => $user->account_status,
                'created_at' => $user->created_at?->timezone(config('venue.timezone'))->format('d/m/Y H:i'),
                'can_edit' => $actor->can('update', $user),
                'can_delete' => $actor->can('delete', $user),
            ]),
            'stats' => $this->users->managementStats(),
            'filters' => ['search' => $filters['search'] ?? '', 'role' => $filters['role'] ?? '', 'status' => $filters['status'] ?? ''],
            'assignableRoles' => array_keys(array_filter(User::ROLES, fn (int $level): bool => $level <= $actor->roleLevel())),
        ]);
    }

    public function store(SaveAdminUserRequest $request): RedirectResponse
    {
        $this->management->save($request->user(), null, $request->validated());

        return to_route('admin.users.index')->with('success', 'Đã thêm người dùng.');
    }

    public function update(SaveAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->management->save($request->user(), $user, $request->validated());

        return back()->with('success', 'Đã cập nhật người dùng.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->management->delete($request->user(), $user);

        return back()->with('success', 'Đã xóa người dùng.');
    }
}
