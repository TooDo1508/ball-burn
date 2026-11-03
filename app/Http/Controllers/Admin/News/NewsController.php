<?php

namespace App\Http\Controllers\Admin\News;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteBlogPostsRequest;
use App\Http\Requests\Admin\IndexBlogPostsRequest;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UploadBlogImageRequest;
use App\Models\BlogPost;
use App\Services\Admin\News\BlogPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(private readonly BlogPostService $posts) {}

    public function index(IndexBlogPostsRequest $request): Response
    {
        $this->authorize('viewAny', BlogPost::class);
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 5);
        $posts = $this->posts->listing($filters)->through(fn (BlogPost $post): array => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'status' => $post->status === 'PUBLISHED' && $post->published_at?->isFuture() ? 'SCHEDULED' : $post->status,
            'published_at' => $post->published_at?->format('Y-m-d H:i'),
            'updated_at' => $post->updated_at?->format('Y-m-d H:i'),
            'cover_image_url' => $this->posts->coverImageUrl($post->cover_image_path),
            'categories' => $post->categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'author' => $post->author?->name,
        ]);

        return Inertia::render('admin/news/Index', [
            'posts' => $posts,
            'stats' => $this->posts->stats(),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'category' => $filters['category'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', BlogPost::class);

        return Inertia::render('admin/news/Form', [
            'post' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);
        $post = $this->posts->create(
            $request->validated(),
            (int) $request->user()->id,
            $request->file('cover_image'),
        );

        return to_route('admin.news.edit', $post)->with('success', 'Bài viết đã được tạo.');
    }

    public function uploadImage(UploadBlogImageRequest $request): JsonResponse
    {
        $this->authorize('create', BlogPost::class);
        $url = $this->posts->storeContentImage($request->file('image'));

        return response()->json(['url' => $url]);
    }

    public function edit(int $postId): Response
    {
        $post = $this->posts->find($postId);
        $this->authorize('update', $post);

        return Inertia::render('admin/news/Form', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'cover_image_path' => $post->cover_image_path,
                'cover_image_url' => $this->posts->coverImageUrl($post->cover_image_path),
                'cover_image_alt' => $post->cover_image_alt,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d\\TH:i'),
                'category_ids' => $post->categories->pluck('id'),
            ],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function preview(int $postId): Response
    {
        $post = $this->posts->find($postId);
        $this->authorize('view', $post);

        return Inertia::render('admin/news/Preview', ['post' => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => $this->posts->safeBody($post->body),
            'status' => $post->status === 'PUBLISHED' && $post->published_at?->isFuture() ? 'SCHEDULED' : $post->status,
            'published_at' => $post->published_at?->format('Y-m-d H:i'),
            'cover_image_url' => $this->posts->coverImageUrl($post->cover_image_path),
            'cover_image_alt' => $post->cover_image_alt,
            'categories' => $post->categories->sortBy('name')->pluck('name'),
            'author' => $post->author?->name,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
        ]]);
    }

    public function update(StoreBlogPostRequest $request, int $postId): RedirectResponse
    {
        $post = $this->posts->find($postId);
        $this->authorize('update', $post);
        $this->posts->update($post, $request->validated(), $request->file('cover_image'));

        return to_route('admin.news.edit', $post)->with('success', 'Bài viết đã được cập nhật.');
    }

    public function destroy(int $postId): RedirectResponse
    {
        $post = $this->posts->find($postId);
        $this->authorize('delete', $post);
        $this->posts->delete($post);

        return to_route('admin.news.index')->with('success', 'Bài viết đã được xóa.');
    }

    public function destroyMany(DeleteBlogPostsRequest $request): RedirectResponse
    {
        $this->authorize('deleteAny', BlogPost::class);
        $deletedCount = $this->posts->deleteMany($request->validated('ids'));

        return to_route('admin.news.index')->with('success', $deletedCount.' bài viết đã được xóa.');
    }

    private function categoryOptions(): array
    {
        return $this->posts->categories()->map(fn ($category): array => [
            'id' => $category->id,
            'name' => $category->name,
        ])->values()->all();
    }
}
