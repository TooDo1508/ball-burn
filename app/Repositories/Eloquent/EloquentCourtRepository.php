<?php

namespace App\Repositories\Eloquent;

use App\Models\Court;
use App\Repositories\Contracts\CourtRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCourtRepository implements CourtRepository
{
    public function paginateActive(int $perPage = 12): LengthAwarePaginator
    {
        return Court::query()->where('is_active', true)->with('images')
            ->orderBy('sort_order')->orderBy('id')->paginate($perPage);
    }

    public function findActiveBySlug(string $slug): ?Court
    {
        return Court::query()->where('is_active', true)->where('slug', $slug)
            ->with('images', 'operatingHours')->first();
    }
}
