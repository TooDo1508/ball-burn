<?php

namespace App\Policies;

use App\Models\BlogCategory;
use App\Models\User;

class BlogCategoryPolicy
{
    public function manage(User $user): bool
    {
        return $user->canAccessAdmin();
    }
}
