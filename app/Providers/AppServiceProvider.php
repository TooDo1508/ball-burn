<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\CourtPolicy;
use App\Services\Payments\ReceiptOcrInterface;
use App\Services\Payments\TesseractReceiptOcrService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReceiptOcrInterface::class, TesseractReceiptOcrService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->canAccessAdmin());
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Court::class, CourtPolicy::class);
        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers());
    }
}
