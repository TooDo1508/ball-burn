<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\News\BlogCategoryController;
use App\Http\Controllers\Admin\News\NewsController;
use App\Services\Settings\HomePageService;

Route::get('/', function (HomePageService $homePage) {
    return \Inertia\Inertia::render('Home', ['content' => $homePage->content()]);
})->name('home');

Route::get('/about', function () {
    $title = 'Về BALLBURN | PickleClub & Bistro';
    $description = 'Khám phá BALLBURN PickleClub & Bistro tại TP. Hồ Chí Minh — điểm hẹn sân pickleball, ẩm thực và cộng đồng dành cho mọi người yêu vận động.';
    $image = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=2000&q=85';

    return view('welcome', [
        'seo' => [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => url('/about'),
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'BALLBURN PickleClub & Bistro',
                'description' => $description,
                'url' => url('/about'),
                'image' => $image,
            ],
        ],
    ]);
})->name('about');
Route::view('/blog', 'welcome');
Route::view('/booking', 'welcome');
Route::view('/contact', 'welcome');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->group(function () {
    Route::inertia('/', 'Dashboard')->name('dashboard');
    Route::get('news', [NewsController::class, 'index'])->name('admin.news.index');
    Route::get('news/create', [NewsController::class, 'create'])->name('admin.news.create');
    Route::post('news', [NewsController::class, 'store'])->name('admin.news.store');
    Route::delete('news/bulk', [NewsController::class, 'destroyMany'])->name('admin.news.bulk-destroy');
    Route::get('news/categories', [BlogCategoryController::class, 'index'])->name('admin.news.categories.index');
    Route::post('news/categories', [BlogCategoryController::class, 'store'])->name('admin.news.categories.store');
    Route::put('news/categories/{category}', [BlogCategoryController::class, 'update'])->name('admin.news.categories.update');
    Route::delete('news/categories/{category}', [BlogCategoryController::class, 'destroy'])->name('admin.news.categories.destroy');
    Route::get('news/{post}/edit', [NewsController::class, 'edit'])->name('admin.news.edit');
    Route::get('news/{post}/preview', [NewsController::class, 'preview'])->name('admin.news.preview');
    Route::put('news/{post}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('news/{post}', [NewsController::class, 'destroy'])->name('admin.news.destroy');
    require __DIR__.'/settings.php';
});
