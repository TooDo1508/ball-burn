<?php

namespace App\Providers;

use App\Repositories\Contracts\BlogCategoryRepository;
use App\Repositories\Contracts\BlogPostRepository;
use App\Repositories\Contracts\BookingRepository;
use App\Repositories\Contracts\ContactMessageRepository;
use App\Repositories\Contracts\CourtRepository;
use App\Repositories\Contracts\SiteSettingRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentBlogCategoryRepository;
use App\Repositories\Eloquent\EloquentBlogPostRepository;
use App\Repositories\Eloquent\EloquentBookingRepository;
use App\Repositories\Eloquent\EloquentContactMessageRepository;
use App\Repositories\Eloquent\EloquentCourtRepository;
use App\Repositories\Eloquent\EloquentSiteSettingRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CourtRepository::class, EloquentCourtRepository::class);
        $this->app->bind(BookingRepository::class, EloquentBookingRepository::class);
        $this->app->bind(ContactMessageRepository::class, EloquentContactMessageRepository::class);
        $this->app->bind(BlogPostRepository::class, EloquentBlogPostRepository::class);
        $this->app->bind(BlogCategoryRepository::class, EloquentBlogCategoryRepository::class);
        $this->app->bind(SiteSettingRepository::class, EloquentSiteSettingRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }
}
