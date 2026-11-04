<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\CourtOperatingHour;
use App\Repositories\Contracts\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EloquentBookingRepository implements BookingRepository
{
    public function createConfirmedBatchForGuest(array $items): array
    {
        return DB::transaction(function () use ($items): array {
            $courtIds = collect($items)->pluck('court.id')->unique()->sort()->values();
            $lockedCourts = [];
            foreach ($courtIds as $courtId) {
                $court = Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
                if (! $court->is_active) {
                    throw ValidationException::withMessages(['slots' => 'Một trong các sân đang tạm ngưng nhận đặt lịch.']);
                }
                $lockedCourts[$court->id] = $court;
            }

            $created = [];
            foreach ($items as $index => $item) {
                $court = $lockedCourts[$item['court']->id];
                $startsAtUtc = $item['starts_at_utc'];
                $endsAtUtc = $item['ends_at_utc'];
                $field = "slots.{$index}.starts_at";
                $hours = CourtOperatingHour::query()
                    ->where('court_id', $court->id)
                    ->where('day_of_week', $item['day_of_week'])
                    ->where('is_closed', false)
                    ->first();

                if (! $hours || $item['local_starts_at'] < substr($hours->opens_at, 0, 5) || $item['local_ends_at'] > substr($hours->closes_at, 0, 5)) {
                    throw ValidationException::withMessages([$field => 'Khung giờ phải nằm trong giờ hoạt động của sân.']);
                }

                $hasClosure = CourtClosure::query()
                    ->where('court_id', $court->id)
                    ->where('starts_at', '<', $endsAtUtc)
                    ->where('ends_at', '>', $startsAtUtc)
                    ->exists();
                $hasBooking = Booking::query()
                    ->where('court_id', $court->id)
                    ->where('starts_at', '<', $endsAtUtc)
                    ->where('ends_at', '>', $startsAtUtc)
                    ->where(function ($query): void {
                        $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                            ->orWhere(function ($query): void {
                                $query->where('status', 'PENDING_PAYMENT')
                                    ->where('payment_deadline_at', '>', now());
                            });
                    })
                    ->exists();

                if ($hasClosure || $hasBooking) {
                    throw ValidationException::withMessages([
                        $field => $hasClosure ? 'Khung giờ này đã bị khóa.' : 'Khung giờ này vừa được người khác đặt. Vui lòng tải lại lịch.',
                    ]);
                }

                $booking = Booking::query()->create([
                    ...$item['attributes'],
                    'court_id' => $court->id,
                    'starts_at' => $startsAtUtc,
                    'ends_at' => $endsAtUtc,
                    'status' => 'CONFIRMED',
                    'confirmed_at' => now(),
                ]);
                $booking->statusHistory()->create([
                    'old_status' => null,
                    'new_status' => 'CONFIRMED',
                    'changed_by' => null,
                    'change_source' => 'CUSTOMER',
                    'note' => 'Khách đặt sân trực tuyến; thanh toán sẽ được bổ sung sau.',
                ]);
                $created[] = $booking->load('court');
            }

            return $created;
        });
    }

    public function createConfirmedForGuest(
        Court $court,
        CarbonImmutable $startsAtUtc,
        CarbonImmutable $endsAtUtc,
        int $dayOfWeek,
        string $localStartsAt,
        string $localEndsAt,
        array $attributes,
    ): Booking {
        return DB::transaction(function () use ($court, $startsAtUtc, $endsAtUtc, $dayOfWeek, $localStartsAt, $localEndsAt, $attributes): Booking {
            $lockedCourt = Court::query()->whereKey($court->id)->lockForUpdate()->firstOrFail();
            if (! $lockedCourt->is_active) {
                throw ValidationException::withMessages(['court_id' => 'Sân đang tạm ngưng nhận đặt lịch.']);
            }

            $hours = CourtOperatingHour::query()
                ->where('court_id', $court->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_closed', false)
                ->first();
            if (! $hours || $localStartsAt < substr($hours->opens_at, 0, 5) || $localEndsAt > substr($hours->closes_at, 0, 5)) {
                throw ValidationException::withMessages(['starts_at' => 'Thời gian đặt phải nằm trong giờ hoạt động của sân.']);
            }

            $hasClosure = CourtClosure::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->exists();
            $hasBooking = Booking::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->where(function ($query): void {
                    $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                        ->orWhere(function ($query): void {
                            $query->where('status', 'PENDING_PAYMENT')
                                ->where('payment_deadline_at', '>', now());
                        });
                })
                ->exists();

            if ($hasClosure || $hasBooking) {
                throw ValidationException::withMessages([
                    'starts_at' => $hasClosure ? 'Khung giờ này đã bị khóa.' : 'Khung giờ này vừa được người khác đặt. Vui lòng chọn giờ khác.',
                ]);
            }

            $booking = Booking::query()->create([
                ...$attributes,
                'court_id' => $court->id,
                'starts_at' => $startsAtUtc,
                'ends_at' => $endsAtUtc,
                'status' => 'CONFIRMED',
                'confirmed_at' => now(),
            ]);
            $booking->statusHistory()->create([
                'old_status' => null,
                'new_status' => 'CONFIRMED',
                'changed_by' => null,
                'change_source' => 'CUSTOMER',
                'note' => 'Khách đặt sân trực tuyến; thanh toán sẽ được bổ sung sau.',
            ]);

            return $booking;
        });
    }

    public function findGuestStatusByTokenHash(string $tokenHash): ?Booking
    {
        return Booking::query()->with('court')->where('status_token_hash', $tokenHash)->first();
    }

    public function createConfirmedForAdmin(
        Court $court,
        CarbonImmutable $startsAtUtc,
        CarbonImmutable $endsAtUtc,
        int $dayOfWeek,
        string $localStartsAt,
        string $localEndsAt,
        array $attributes,
        int $userId,
    ): Booking {
        return DB::transaction(function () use ($court, $startsAtUtc, $endsAtUtc, $dayOfWeek, $localStartsAt, $localEndsAt, $attributes, $userId): Booking {
            $lockedCourt = Court::query()->whereKey($court->id)->lockForUpdate()->firstOrFail();
            if (! $lockedCourt->is_active) {
                throw ValidationException::withMessages(['court_id' => 'Sân đang tạm ngưng nhận đặt lịch.']);
            }

            $hours = CourtOperatingHour::query()
                ->where('court_id', $court->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_closed', false)
                ->first();
            if (! $hours || $localStartsAt < substr($hours->opens_at, 0, 5) || $localEndsAt > substr($hours->closes_at, 0, 5)) {
                throw ValidationException::withMessages(['starts_at' => 'Thời gian đặt phải nằm trong giờ hoạt động của sân.']);
            }

            $hasClosure = CourtClosure::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->exists();
            $hasBooking = Booking::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->where(function ($query): void {
                    $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                        ->orWhere(function ($query): void {
                            $query->where('status', 'PENDING_PAYMENT')
                                ->where('payment_deadline_at', '>', now());
                        });
                })
                ->exists();

            if ($hasClosure || $hasBooking) {
                throw ValidationException::withMessages([
                    'starts_at' => $hasClosure ? 'Khoảng thời gian này đã bị khóa.' : 'Khoảng thời gian này đã có người đặt.',
                ]);
            }

            $booking = Booking::query()->create([
                ...$attributes,
                'court_id' => $court->id,
                'starts_at' => $startsAtUtc,
                'ends_at' => $endsAtUtc,
                'status' => 'CONFIRMED',
                'confirmed_at' => now(),
            ]);
            $booking->statusHistory()->create([
                'old_status' => null,
                'new_status' => 'CONFIRMED',
                'changed_by' => $userId,
                'change_source' => 'ADMIN',
                'note' => 'Admin đặt sân thay khách hàng.',
            ]);

            return $booking;
        });
    }
}
