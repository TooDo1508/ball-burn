<?php

namespace App\Repositories\Contracts;

use App\Models\Court;
use App\Models\CourtClosure;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CourtRepository
{
    public function paginateActive(int $perPage = 12): LengthAwarePaginator;

    public function findActiveBySlug(string $slug): ?Court;

    public function findActiveById(int $id): ?Court;

    public function allActiveForBooking(): Collection;

    public function allForAdmin(): Collection;

    public function findForAdmin(int $id): Court;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    public function saveForAdmin(Court $court, array $attributes, array $operatingHours, array $priceRules = [], ?array $image = null): Court;

    public function bookingsBetween(CarbonImmutable $startsAt, CarbonImmutable $endsAt): Collection;

    public function closuresBetween(CarbonImmutable $startsAt, CarbonImmutable $endsAt): Collection;

    public function createClosure(int $courtId, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?string $reason, int $userId): CourtClosure;

    public function deleteClosure(int $closureId): void;
}
