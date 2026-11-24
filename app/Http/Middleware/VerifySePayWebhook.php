<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySePayWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('sepay.webhook_api_key', '');
        $authorization = (string) $request->header('Authorization', '');
        $provided = str_starts_with($authorization, 'Apikey ') ? substr($authorization, 7) : '';

        if ($secret === '' || $provided === '' || ! hash_equals($secret, $provided)) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
