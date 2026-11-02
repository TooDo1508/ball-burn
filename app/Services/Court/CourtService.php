<?php

namespace App\Services\Court;

use App\Models\Court;
use App\Repositories\Contracts\CourtRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourtService
{
    public function __construct(private readonly CourtRepository $courts) {}

    public function paginateActive(): LengthAwarePaginator
    {
        return $this->courts->paginateActive();
    }

    public function findActiveBySlug(string $slug): ?Court
    {
        return $this->courts->findActiveBySlug($slug);
    }
}
