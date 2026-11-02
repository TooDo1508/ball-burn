<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Repositories\Contracts\BlogCategoryRepository;
use Illuminate\Support\Collection;

class EloquentBlogCategoryRepository implements BlogCategoryRepository
{
    public function all(): Collection
    {
        return BlogCategory::query()->withCount('posts')->orderBy('name')->get();
    }

    public function findForAdmin(int $id): BlogCategory
    {
        return BlogCategory::query()->findOrFail($id);
    }

    public function idsExist(array $ids): bool
    {
        return BlogCategory::query()->whereKey($ids)->count() === count(array_unique($ids));
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return BlogCategory::query()->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        return BlogCategory::query()->where('name', $name)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function save(BlogCategory $category, array $attributes): BlogCategory
    {
        $category->fill($attributes)->save();

        return $category;
    }

    public function delete(BlogCategory $category): void
    {
        $category->delete();
    }
}
