<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Repositories\Contracts\BlogPostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentBlogPostRepository implements BlogPostRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 5): LengthAwarePaginator
    {
        return BlogPost::query()
            ->with(['author:id,name', 'categories:id,name,slug'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, string $status): void {
                if ($status === 'SCHEDULED') {
                    $query->where('status', 'PUBLISHED')->where('published_at', '>', now());
                } elseif ($status === 'PUBLISHED') {
                    $query->where('status', 'PUBLISHED')->where('published_at', '<=', now());
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->whereHas('categories', fn ($query) => $query->whereKey($category)))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('published_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('published_at', '<=', $to))
            ->orderByRaw("CASE WHEN status = 'PUBLISHED' AND published_at > ? THEN 0 ELSE 1 END", [now()])
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatePublished(int $perPage = 12): LengthAwarePaginator
    {
        return BlogPost::query()
            ->with(['author:id,name', 'categories:id,name,slug'])
            ->where('status', 'PUBLISHED')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): BlogPost
    {
        return BlogPost::query()
            ->with(['author:id,name', 'categories:id,name,slug'])
            ->where('status', 'PUBLISHED')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function stats(): array
    {
        $now = now();

        return [
            'total' => BlogPost::query()->count(),
            'published' => BlogPost::query()->where('status', 'PUBLISHED')->where('published_at', '<=', $now)->count(),
            'draft' => BlogPost::query()->where('status', 'DRAFT')->count(),
            'scheduled' => BlogPost::query()->where('status', 'PUBLISHED')->where('published_at', '>', $now)->count(),
        ];
    }

    public function findForAdmin(int $id): BlogPost
    {
        return BlogPost::query()->with(['author:id,name', 'categories:id,name,slug'])->findOrFail($id);
    }

    public function findManyForAdmin(array $ids): Collection
    {
        return BlogPost::query()->whereKey($ids)->get();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return BlogPost::query()->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function save(BlogPost $post, array $attributes, array $categoryIds): BlogPost
    {
        return DB::transaction(function () use ($post, $attributes, $categoryIds): BlogPost {
            $post->fill($attributes);
            $post->save();
            $post->categories()->sync(BlogCategory::query()->whereKey($categoryIds)->pluck('id'));

            return $post->load(['author:id,name', 'categories:id,name']);
        });
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    public function deleteMany(array $ids): void
    {
        DB::transaction(fn () => BlogPost::query()->whereKey($ids)->delete());
    }
}
