<?php

namespace App\Repositories\Contracts;

use App\Models\ContactMessage;

interface ContactMessageRepository
{
    public function create(array $attributes): ContactMessage;
}
