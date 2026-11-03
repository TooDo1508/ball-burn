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
