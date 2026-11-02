<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->canAccessAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }
}
