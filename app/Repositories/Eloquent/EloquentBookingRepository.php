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
