<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            abort_unless($request->user()->canAccessAdmin(), 403);
        }

        return $next($request);
    }
}
