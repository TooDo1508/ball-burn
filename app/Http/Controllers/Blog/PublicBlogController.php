<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Blog\PublicBlogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class PublicBlogController extends Controller
{
    public function __construct(private readonly PublicBlogService $blog) {}

    public function indexPage(): View
    {
        $title = 'Tin tức pickleball, luật chơi và mẹo tập luyện | BALLBURN';
        $description = 'Cẩm nang pickleball bằng tiếng Việt: luật chơi, chiến thuật, dụng cụ và văn hóa giao lưu từ BALLBURN PickleClub & Bistro tại Hà Nội.';
        $canonical = url('/blog');
        $listing = $this->blog->listing();

        return view('welcome', ['seo' => [
            'title' => $title,
            'description' => $description,
            'image' => url('/images/pickleball/court-rally-wide.jpg'),
            'canonical' => $canonical,
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'Blog',
                'name' => 'Tin tức pickleball BALLBURN',
                'description' => $description,
                'url' => $canonical,
                'publisher' => ['@type' => 'Organization', 'name' => 'BALLBURN PickleClub & Bistro'],
            ],
        ], 'initialBlogPosts' => $listing->getCollection()->map(fn (BlogPost $post): array => $this->summary($post))->all(), 'initialBlogPostsNextPageUrl' => $listing->nextPageUrl()]);
    }

    public function showPage(string $slug): View
    {
        $post = $this->blog->find($slug);
        $description = $post->meta_description ?: $post->excerpt ?: $post->title;
        $seo = $this->articleSeo($post, $description);

        return view('welcome', [
            'seo' => $seo,
            'initialBlogPost' => [
                ...$this->summary($post),
                'body' => $this->blog->safeBody($post->body),
                'author' => $post->author?->name ?: 'BALLBURN PickleClub & Bistro',
                'seo' => $seo,
            ],
        ]);
    }

    public function posts(): JsonResponse
    {
        $posts = $this->blog->listing()->through(fn (BlogPost $post): array => [
            ...$this->summary($post),
            'url' => url('/blog/'.$post->slug),
        ]);

        return response()->json($posts);
    }

    public function post(string $slug): JsonResponse
    {
        $post = $this->blog->find($slug);
        $description = $post->meta_description ?: $post->excerpt ?: $post->title;

        return response()->json(['data' => [
            ...$this->summary($post),
            'body' => $this->blog->safeBody($post->body),
            'updated_at' => $post->updated_at?->toAtomString(),
            'author' => $post->author?->name ?: 'BALLBURN PickleClub & Bistro',
            'seo' => $this->articleSeo($post, $description),
        ]]);
    }

    private function summary(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'cover_image_url' => $this->blog->imageUrl($post->cover_image_path),
            'cover_image_alt' => $post->cover_image_alt,
            'published_at' => $post->published_at?->toAtomString(),
            'categories' => $post->categories->pluck('name')->values(),
        ];
    }

    private function articleSeo(BlogPost $post, string $description): array
    {
        $title = $post->meta_title ?: $post->title.' | BALLBURN';
        $canonical = url('/blog/'.$post->slug);
        $image = $this->blog->imageUrl($post->cover_image_path);
        $publishedAt = $post->published_at?->toAtomString();
        $modifiedAt = $post->updated_at?->toAtomString();
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $description,
            'datePublished' => $publishedAt,
            'dateModified' => $modifiedAt,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'url' => $canonical,
            'author' => ['@type' => 'Organization', 'name' => $post->author?->name ?: 'BALLBURN PickleClub & Bistro'],
            'publisher' => ['@type' => 'Organization', 'name' => 'BALLBURN PickleClub & Bistro'],
            'articleSection' => $post->categories->pluck('name')->values()->all(),
            'inLanguage' => 'vi-VN',
        ];

        if ($image) {
            $structuredData['image'] = [$image];
        }

        $seo = [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => $canonical,
            'type' => 'article',
            'published_at' => $publishedAt,
            'modified_at' => $modifiedAt,
            'structured_data' => $structuredData,
        ];

        return $seo;
    }
}
