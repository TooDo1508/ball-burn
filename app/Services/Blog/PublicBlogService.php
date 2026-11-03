<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogPostRepository;
use App\Services\Content\BlogContentSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PublicBlogService
{
    public function __construct(
        private readonly BlogPostRepository $posts,
        private readonly BlogContentSanitizer $contentSanitizer,
    ) {}

    public function listing(int $perPage = 12): LengthAwarePaginator
    {
        return $this->posts->paginatePublished($perPage);
    }

    public function find(string $slug): BlogPost
    {
        return $this->posts->findPublishedBySlug($slug);
    }

    public function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
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
