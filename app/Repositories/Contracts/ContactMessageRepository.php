<?php

namespace App\Repositories\Contracts;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContactMessageRepository
{
    public function create(array $attributes): ContactMessage;

    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function stats(): array;

    public function findForAdmin(int $id): ContactMessage;

    public function update(ContactMessage $message, array $attributes): ContactMessage;
}
