<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogPostRepository;
use App\Services\Content\BlogContentSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorResult;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicBlogService
{
    private const VERSION_CACHE_KEY = 'public-blog:version';

    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly BlogPostRepository $posts,
        private readonly BlogContentSanitizer $contentSanitizer,
    ) {}

    public function listing(int $perPage = 12): LengthAwarePaginator
    {
        $version = Cache::get(self::VERSION_CACHE_KEY, '1');
        $page = Paginator::resolveCurrentPage();
        $key = "public-blog:list:{$version}:{$perPage}:{$page}";
        $payload = Cache::remember(
            $key,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->listingPayload($perPage),
        );

        return new PaginatorResult($payload['items'], $payload['total'], $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    public function find(string $slug): array
    {
        $version = Cache::get(self::VERSION_CACHE_KEY, '1');

        return Cache::remember(
            'public-blog:post:'.$version.':'.hash('sha256', $slug),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->postPayload($this->posts->findPublishedBySlug($slug)),
        );
    }

    public static function invalidateCache(): void
    {
        Cache::forever(self::VERSION_CACHE_KEY, (string) Str::uuid());
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int} */
    private function listingPayload(int $perPage): array
    {
        $page = $this->posts->paginatePublished($perPage);

        return [
            'items' => $page->getCollection()->map(fn (BlogPost $post): array => $this->postPayload($post))->all(),
            'total' => $page->total(),
        ];
    }

    /** @return array<string, mixed> */
    private function postPayload(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'cover_image_path' => $post->cover_image_path,
            'cover_image_alt' => $post->cover_image_alt,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'published_at' => $post->published_at?->toAtomString(),
            'updated_at' => $post->updated_at?->toAtomString(),
            'author' => $post->author?->name,
            'categories' => $post->categories->pluck('name')->values()->all(),
        ];
    }

    public function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            if (str_contains($path, 'images.unsplash.com')) {
                return match (true) {
                    str_contains($path, 'photo-1554068865') => '/images/pickleball/player-action.jpg',
                    str_contains($path, 'photo-1546483875') => '/images/pickleball/court-community.jpg',
                    str_contains($path, 'photo-1622279457486') => '/images/pickleball/paddle-court.jpg',
                    str_contains($path, 'photo-1595435934249') => '/images/pickleball/court-rally-wide.jpg',
                    default => '/images/pickleball/court-rally-wide.jpg',
                };
            }

            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.$path;
    }

    public function safeBody(string $body): string
    {
        return $this->contentSanitizer->sanitize($body);
    }

}
