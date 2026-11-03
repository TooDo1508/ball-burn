<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactMessageRequest;
use App\Services\Contact\ContactMessageService;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $messages) {}

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $this->messages->submit($request->validated());

        return response()->json([
            'message' => 'Cảm ơn bạn đã liên hệ BALLBURN. Chúng tôi sẽ phản hồi sớm nhất có thể.',
        ], 201);
    }
}
