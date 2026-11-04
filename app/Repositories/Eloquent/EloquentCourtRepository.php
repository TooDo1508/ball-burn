<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Repositories\Contracts\CourtRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EloquentCourtRepository implements CourtRepository
{
    public function paginateActive(int $perPage = 12): LengthAwarePaginator
    {
        return Court::query()->where('is_active', true)->with('images', 'priceRules')
            ->orderBy('sort_order')->orderBy('id')->paginate($perPage);
    }

    public function findActiveBySlug(string $slug): ?Court
    {
        return Court::query()->where('is_active', true)->where('slug', $slug)
            ->with('images', 'operatingHours', 'priceRules')->first();
    }

    public function findActiveById(int $id): ?Court
    {
        return Court::query()->where('is_active', true)->whereKey($id)
            ->with('images', 'operatingHours', 'priceRules')->first();
    }

    public function allActiveForBooking(): Collection
    {
        return Court::query()->where('is_active', true)
            ->with(['images', 'operatingHours', 'priceRules'])
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function allForAdmin(): Collection
    {
        return Court::query()
            ->with(['images', 'operatingHours', 'priceRules'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findForAdmin(int $id): Court
    {
        return Court::query()->with(['images', 'operatingHours', 'priceRules'])->findOrFail($id);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Court::query()->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function saveForAdmin(Court $court, array $attributes, array $operatingHours, array $priceRules = [], ?array $image = null): Court
    {
        return DB::transaction(function () use ($court, $attributes, $operatingHours, $priceRules, $image): Court {
            if ($court->exists) {
                Court::query()->whereKey($court->id)->lockForUpdate()->firstOrFail();
            }

            $court->fill($attributes);
            $court->save();
            $court->operatingHours()->delete();
            $court->operatingHours()->createMany($operatingHours);

            if ($priceRules !== []) {
                $court->priceRules()->delete();
                $court->priceRules()->createMany($priceRules);
            }

            if ($image) {
                $court->images()->where('is_primary', true)->delete();
                $court->images()->create($image);
            }

            return $court->load(['images', 'operatingHours', 'priceRules']);
        });
    }

    public function bookingsBetween(CarbonImmutable $startsAt, CarbonImmutable $endsAt): Collection
    {
        return Booking::query()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->where(function ($query): void {
                $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                    ->orWhere(function ($query): void {
                        $query->where('status', 'PENDING_PAYMENT')
                            ->where('payment_deadline_at', '>', now());
                    });
            })
            ->orderBy('starts_at')
            ->get();
    }

    public function closuresBetween(CarbonImmutable $startsAt, CarbonImmutable $endsAt): Collection
    {
        return CourtClosure::query()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->orderBy('starts_at')
            ->get();
    }

    public function createClosure(int $courtId, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?string $reason, int $userId): CourtClosure
    {
        return DB::transaction(function () use ($courtId, $startsAt, $endsAt, $reason, $userId): CourtClosure {
            Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();

            $closureExists = CourtClosure::query()
                ->where('court_id', $courtId)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            $bookingExists = Booking::query()
                ->where('court_id', $courtId)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->where(function ($query): void {
                    $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                        ->orWhere(function ($query): void {
                            $query->where('status', 'PENDING_PAYMENT')
                                ->where('payment_deadline_at', '>', now());
                        });
                })
                ->exists();

            if ($closureExists || $bookingExists) {
                throw ValidationException::withMessages([
                    'starts_at' => $bookingExists
                        ? 'Khoảng thời gian này đã có lịch đặt sân.'
                        : 'Khoảng thời gian này đã nằm trong một lịch khóa khác.',
                ]);
            }

            return CourtClosure::query()->create([
                'court_id' => $courtId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function deleteClosure(int $closureId): void
    {
        DB::transaction(function () use ($closureId): void {
            $closure = CourtClosure::query()->lockForUpdate()->findOrFail($closureId);
            Court::query()->whereKey($closure->court_id)->lockForUpdate()->firstOrFail();
            $closure->delete();
        });
    }
}
