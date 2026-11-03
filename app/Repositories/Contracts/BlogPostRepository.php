<?php

namespace App\Repositories\Contracts;

use App\Models\BlogPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BlogPostRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 5): LengthAwarePaginator;

    public function paginatePublished(int $perPage = 12): LengthAwarePaginator;

    public function findPublishedBySlug(string $slug): BlogPost;

    public function stats(): array;

    public function findForAdmin(int $id): BlogPost;

    public function findManyForAdmin(array $ids): Collection;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    public function save(BlogPost $post, array $attributes, array $categoryIds): BlogPost;

    public function delete(BlogPost $post): void;

    public function deleteMany(array $ids): void;
}
