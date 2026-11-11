<?php

namespace App\Services\Admin\News;

use App\Models\BlogCategory;
use App\Repositories\Contracts\BlogCategoryRepository;
use App\Services\Blog\PublicBlogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlogCategoryService
{
    public function __construct(private readonly BlogCategoryRepository $categories) {}

    public function listing(): Collection
    {
        return $this->categories->all();
    }

    public function find(int $id): BlogCategory
    {
        return $this->categories->findForAdmin($id);
    }

    public function create(array $validated): BlogCategory
    {
        $category = $this->categories->save(new BlogCategory, $this->attributes($validated));
        PublicBlogService::invalidateCache();

        return $category;
    }

    public function update(BlogCategory $category, array $validated): BlogCategory
    {
        $saved = $this->categories->save($category, $this->attributes($validated, $category->id));
        PublicBlogService::invalidateCache();

        return $saved;
    }

    public function delete(BlogCategory $category): void
    {
        $this->categories->delete($category);
        PublicBlogService::invalidateCache();
    }

    private function attributes(array $validated, ?int $ignoreId = null): array
    {
        $name = trim($validated['name']);
        if ($this->categories->nameExists($name, $ignoreId)) {
            throw ValidationException::withMessages(['name' => 'Tên danh mục này đã được sử dụng.']);
        }

        $requestedSlug = trim((string) ($validated['slug'] ?? ''));
        $base = Str::slug($requestedSlug ?: $name) ?: 'danh-muc';
        if ($this->categories->slugExists($base, $ignoreId) && $requestedSlug !== '') {
            throw ValidationException::withMessages(['slug' => 'Slug danh mục này đã được sử dụng.']);
        }

        $slug = $base;
        $suffix = 2;
        while ($this->categories->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix++;
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ];
    }
}
