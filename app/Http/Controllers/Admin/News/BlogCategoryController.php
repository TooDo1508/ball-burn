<?php

namespace App\Http\Controllers\Admin\News;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Services\Admin\News\BlogCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BlogCategoryController extends Controller
{
    public function __construct(private readonly BlogCategoryService $categories) {}

    public function index(): Response
    {
        $this->authorize('manage', BlogCategory::class);

        return Inertia::render('admin/news/Categories', [
            'categories' => $this->categories->listing()->map(fn (BlogCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'posts_count' => $category->posts_count,
            ]),
        ]);
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $this->authorize('manage', BlogCategory::class);
        $this->categories->create($request->validated());

        return to_route('admin.news.categories.index')->with('success', 'Danh mục đã được tạo.');
    }

    public function update(StoreBlogCategoryRequest $request, int $categoryId): RedirectResponse
    {
        $this->authorize('manage', BlogCategory::class);
        $category = $this->categories->find($categoryId);
        $this->categories->update($category, $request->validated());

        return to_route('admin.news.categories.index')->with('success', 'Danh mục đã được cập nhật.');
    }

    public function destroy(int $categoryId): RedirectResponse
    {
        $this->authorize('manage', BlogCategory::class);
        $category = $this->categories->find($categoryId);
        $this->categories->delete($category);

        return to_route('admin.news.categories.index')->with('success', 'Danh mục đã được xóa khỏi các bài viết liên quan.');
    }
}
