<?php

namespace App\Repositories\Contracts;

use App\Models\BlogCategory;
use Illuminate\Support\Collection;

interface BlogCategoryRepository
{
    public function all(): Collection;

    public function findForAdmin(int $id): BlogCategory;

    public function idsExist(array $ids): bool;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    public function nameExists(string $name, ?int $ignoreId = null): bool;

    public function save(BlogCategory $category, array $attributes): BlogCategory;

    public function delete(BlogCategory $category): void;
}
