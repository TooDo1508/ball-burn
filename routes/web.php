<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/about', 'welcome');
Route::view('/blog', 'welcome');
Route::view('/booking', 'welcome');
Route::view('/contact', 'welcome');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->group(function () {
    Route::inertia('/', 'Dashboard')->name('dashboard');
    require __DIR__.'/settings.php';
});
