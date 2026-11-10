<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAdminUsersRequest;
use App\Repositories\Contracts\UserRepository;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function index(IndexAdminUsersRequest $request): Response
    {
        $search = $request->validated('search');
        $users = $this->users->paginateForAdmin($search);

        return Inertia::render('admin/users/Index', [
            'users' => $users->through(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => strtoupper((string) $user->role),
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
            ]),
            'filters' => ['search' => $search ?? ''],
        ]);
    }
}
