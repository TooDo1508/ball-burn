<?php

namespace App\Services\Contact;

use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public function __construct(private readonly ContactMessageRepository $messages) {}

    public function submit(array $attributes): void
    {
        unset($attributes['website']);
        $attributes['status'] = 'NEW';
        $this->messages->create($attributes);
    }

    public function listing(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->messages->paginateForAdmin($filters, $perPage);
    }

    public function stats(): array
    {
        return $this->messages->stats();
    }

    public function find(int $id): ContactMessage
    {
        return $this->messages->findForAdmin($id);
    }

    public function update(ContactMessage $message, array $attributes): ContactMessage
    {
        $attributes['resolved_at'] = $attributes['status'] === 'RESOLVED' ? now() : null;

        return $this->messages->update($message, $attributes);
    }
}
