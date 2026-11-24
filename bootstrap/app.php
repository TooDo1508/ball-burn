<?php

use App\Http\Middleware\AddRobotsDirectives;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddRobotsDirectives::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn (Request $request): bool => $request->routeIs(
            'courts.*',
            'blog.posts',
            'blog.post',
            'site.contact-info',
            'booking.availability',
            'booking.holds.*',
            'booking.payment-instructions',
            'booking.payment-proof',
            'booking.status',
            'booking.lookup.submit',
            'contact.store',
            'webhooks.sepay',
            'admin.bookings.notifications*',
            'admin.news.images',
        ) || $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApiRequest);
        $exceptions->render(function (Throwable $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => $exception->status,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => config('api.error_message'),
            ], $status)->header('Cache-Control', 'no-store, private');
        });
    })->create();
