<?php

use App\Http\Controllers\Blog\PublicBlogController;
use App\Http\Controllers\Contact\ContactPageController;
use App\Http\Controllers\Court\CourtController;
use App\Http\Controllers\Payments\SePayWebhookController;
use App\Http\Middleware\AddRobotsDirectives;
use App\Http\Middleware\VerifySePayWebhook;
use Illuminate\Support\Facades\Route;

Route::post('/api/webhooks/sepay', SePayWebhookController::class)
    ->middleware([VerifySePayWebhook::class, 'throttle:120,1'])
    ->name('webhooks.sepay');

Route::middleware(AddRobotsDirectives::class)->group(function (): void {
    Route::get('/courts', [CourtController::class, 'index'])->name('courts.index');
    Route::get('/courts/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
    Route::get('/api/blog/posts', [PublicBlogController::class, 'posts'])->name('blog.posts');
    Route::get('/api/blog/posts/{slug}', [PublicBlogController::class, 'post'])->name('blog.post');
    Route::get('/api/site/contact-info', [ContactPageController::class, 'publicInfo'])->name('site.contact-info');
});
