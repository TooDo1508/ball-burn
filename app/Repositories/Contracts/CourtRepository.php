<?php

namespace App\Repositories\Contracts;

use App\Models\Court;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CourtRepository
{
    public function paginateActive(int $perPage = 12): LengthAwarePaginator;

    public function findActiveBySlug(string $slug): ?Court;
}
