<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\HomePageController;
use App\Http\Controllers\Settings\ContactSettingsController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/admin/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/home-page', [HomePageController::class, 'edit'])->name('settings.home-page.edit');
    Route::put('settings/home-page', [HomePageController::class, 'update'])->name('settings.home-page.update');
    Route::get('settings/contact', [ContactSettingsController::class, 'edit'])->name('settings.contact.edit');
    Route::put('settings/contact', [ContactSettingsController::class, 'update'])->name('settings.contact.update');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('settings/security', [SecurityController::class, 'edit'])

        ->middleware(RequirePassword::class)

        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
