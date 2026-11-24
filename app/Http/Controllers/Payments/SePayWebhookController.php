<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\SePayWebhookRequest;
use App\Services\Payments\SePayWebhookService;
use Illuminate\Http\JsonResponse;

class SePayWebhookController extends Controller
{
    public function __invoke(SePayWebhookRequest $request, SePayWebhookService $service): JsonResponse
    {
        $service->receive($request->validated());

        return response()->json(['success' => true]);
    }
}
