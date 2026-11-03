<?php

namespace App\Services\Contact;

use App\Repositories\Contracts\ContactMessageRepository;

class ContactMessageService
{
    public function __construct(private readonly ContactMessageRepository $messages) {}

    public function submit(array $attributes): void
    {
        unset($attributes['website']);
        $attributes['status'] = 'NEW';
        $this->messages->create($attributes);
    }
}
