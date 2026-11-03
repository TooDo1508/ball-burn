<?php

use App\Http\Controllers\Court\CourtController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Contact\ContactPageController;

Route::get('/courts', [CourtController::class, 'index'])->name('courts.index');
Route::get('/courts/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
Route::get('/api/blog/posts', [\App\Http\Controllers\Blog\PublicBlogController::class, 'posts'])->name('blog.posts');
Route::get('/api/blog/posts/{slug}', [\App\Http\Controllers\Blog\PublicBlogController::class, 'post'])->name('blog.post');
Route::get('/api/site/contact-info', [ContactPageController::class, 'publicInfo'])->name('site.contact-info');
