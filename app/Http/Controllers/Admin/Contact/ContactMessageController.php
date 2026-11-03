<?php

namespace App\Http\Controllers\Admin\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexContactMessagesRequest;
use App\Http\Requests\Admin\UpdateContactMessageRequest;
use App\Services\Contact\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $messages) {}

    public function index(IndexContactMessagesRequest $request): Response
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 10);
        $messages = $this->messages->listing($filters, $perPage)->through(fn ($message): array => [
            'id' => $message->id,
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'subject' => $message->subject,
            'message' => $message->message,
            'status' => $message->status,
            'admin_notes' => $message->admin_notes,
            'created_at' => $message->created_at?->format('Y-m-d H:i'),
        ]);

        return Inertia::render('admin/contact-messages/Index', [
            'messages' => $messages,
            'stats' => $this->messages->stats(),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(int $messageId): Response
    {
        $message = $this->messages->find($messageId);

        return Inertia::render('admin/contact-messages/Show', [
            'message' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status,
                'admin_notes' => $message->admin_notes,
                'created_at' => $message->created_at?->format('Y-m-d H:i:s'),
                'resolved_at' => $message->resolved_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function update(UpdateContactMessageRequest $request, int $messageId): RedirectResponse
    {
        $message = $this->messages->find($messageId);
        $this->messages->update($message, $request->validated());

        return back()->with('success', 'Đã cập nhật yêu cầu liên hệ.');
    }
}
