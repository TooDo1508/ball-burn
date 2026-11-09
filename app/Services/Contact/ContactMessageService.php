<?php

namespace App\Services\Contact;

use App\Jobs\SendContactMessageAdminNotification;
use App\Jobs\SendContactMessageAcknowledgement;
use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepository;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public function __construct(
        private readonly ContactMessageRepository $messages,
        private readonly ContactSettingsService $settings,
    ) {}

    public function submit(array $attributes): void
    {
        unset($attributes['website']);
        $attributes['status'] = 'NEW';
        $message = $this->messages->create($attributes);

        SendContactMessageAcknowledgement::dispatch($message)->afterCommit();

        $adminEmail = trim((string) ($this->settings->publicSettings()['email'] ?? ''));
        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            SendContactMessageAdminNotification::dispatch($message, $adminEmail)->afterCommit();
        }
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
