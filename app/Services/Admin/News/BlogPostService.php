<?php

namespace App\Services\Admin\News;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogCategoryRepository;
use App\Repositories\Contracts\BlogPostRepository;
use App\Services\Blog\PublicBlogService;
use App\Services\Content\BlogContentSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BlogPostService
{
    public function __construct(
        private readonly BlogPostRepository $posts,
        private readonly BlogCategoryRepository $categories,
        private readonly BlogContentSanitizer $contentSanitizer,
    ) {}

    public function listing(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 5);

        return $this->posts->paginateForAdmin($filters, $perPage);
    }

    public function stats(): array
    {
        return $this->posts->stats();
    }

    public function categories(): Collection
    {
        return $this->categories->all();
    }

    public function coverImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.$path;
    }

    public function storeContentImage(UploadedFile $image): string
    {
        $path = $image->store('blog/content', 'public');

        return '/storage/'.$path;
    }

    public function safeBody(string $body): string
    {
        return $this->contentSanitizer->sanitize($body);
    }

    public function find(int $id): BlogPost
    {
        return $this->posts->findForAdmin($id);
    }

    public function create(array $validated, int $authorId, ?UploadedFile $cover): BlogPost
    {
        $attributes = $this->attributes($validated);
        $attributes['author_id'] = $authorId;
        $newCover = $cover?->store('blog/covers', 'public');
        if ($newCover) {
            $attributes['cover_image_path'] = $newCover;
        }

        try {
            $post = $this->posts->save(new BlogPost, $attributes, $this->categoryIds($validated));
        } catch (Throwable $exception) {
            if ($newCover) {
                Storage::disk('public')->delete($newCover);
            }

            throw $exception;
        }

        PublicBlogService::invalidateCache();

        return $post;
    }

    public function update(BlogPost $post, array $validated, ?UploadedFile $cover): BlogPost
    {
        $attributes = $this->attributes($validated, $post->id);
        $oldCover = $post->cover_image_path;
        $newCover = $cover?->store('blog/covers', 'public');
        if ($newCover) {
            $attributes['cover_image_path'] = $newCover;
        } elseif ($validated['remove_cover_image'] ?? false) {
            $attributes['cover_image_path'] = null;
            $attributes['cover_image_alt'] = null;
        }

        try {
            $saved = $this->posts->save($post, $attributes, $this->categoryIds($validated));
        } catch (Throwable $exception) {
            if ($newCover) {
                Storage::disk('public')->delete($newCover);
            }

            throw $exception;
        }

        if ($this->isStoredPath($oldCover) && ($newCover || ($validated['remove_cover_image'] ?? false))) {
            Storage::disk('public')->delete($oldCover);
        }

        PublicBlogService::invalidateCache();

        return $saved;
    }

    public function delete(BlogPost $post): void
    {
        $cover = $post->cover_image_path;
        $this->posts->delete($post);
        PublicBlogService::invalidateCache();
        if ($this->isStoredPath($cover)) {
            Storage::disk('public')->delete($cover);
        }
    }

    public function deleteMany(array $ids): int
    {
        /** @var Collection<int, BlogPost> $posts */
        $posts = $this->posts->findManyForAdmin($ids);
        if ($posts->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['ids' => 'Một hoặc nhiều bài viết không còn tồn tại.']);
        }

        $covers = $posts->pluck('cover_image_path')->filter(fn (?string $path): bool => $this->isStoredPath($path))->all();
        $this->posts->deleteMany($ids);
        PublicBlogService::invalidateCache();
        Storage::disk('public')->delete($covers);

        return count($ids);
    }

    private function attributes(array $validated, ?int $ignoreId = null): array
    {
        $requestedSlug = trim((string) ($validated['slug'] ?? ''));
        $slug = $this->uniqueSlug($requestedSlug ?: $validated['title'], $validated['title'], $ignoreId, $requestedSlug !== '');
        $status = $validated['status'];
        $publishedAt = $status === 'DRAFT'
            ? null
            : ($validated['published_at'] ?? ($status === 'PUBLISHED' ? now() : null));

        return [
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $this->contentSanitizer->sanitize($validated['body']),
            'cover_image_alt' => $validated['cover_image_alt'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function uniqueSlug(string $value, string $fallback, ?int $ignoreId, bool $isManual): string
    {
        $base = Str::slug($value) ?: Str::slug($fallback) ?: 'bai-viet';
        if (! $this->posts->slugExists($base, $ignoreId)) {
            return $base;
        }
        if ($isManual) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn này đã được sử dụng.']);
        }

        $slug = $base;
        $suffix = 2;
        while ($this->posts->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function categoryIds(array $validated): array
    {
        $ids = array_map('intval', $validated['category_ids'] ?? []);
        if (! $this->categories->idsExist($ids)) {
            throw ValidationException::withMessages(['category_ids' => 'Một hoặc nhiều danh mục không còn tồn tại.']);
        }

        return $ids;
    }

    private function isStoredPath(?string $path): bool
    {
        return filled($path) && ! preg_match('~^https?://~i', $path) && ! str_starts_with($path, '/');
    }
}
