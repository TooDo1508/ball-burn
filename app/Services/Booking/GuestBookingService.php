<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Repositories\Contracts\BookingRepository;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Court\CourtPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestBookingService
{
    public function __construct(
        private readonly CourtRepository $courts,
        private readonly BookingRepository $bookings,
        private readonly CourtPricingService $pricing,
    ) {}

    public function availability(string $dateValue, ?int $courtId = null): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateValue, $timezone);
        $this->validateBookingDate($date, $timezone);

        $courts = $this->courts->allActiveForBooking();
        $selectedCourt = $courtId ? $courts->firstWhere('id', $courtId) : $courts->first();
        if ($courtId && ! $selectedCourt) {
            throw ValidationException::withMessages(['court_id' => 'Sân không còn nhận đặt lịch.']);
        }

        $dayStartUtc = $date->startOfDay()->utc();
        $dayEndUtc = $date->addDay()->startOfDay()->utc();
        $bookings = $this->courts->bookingsBetween($dayStartUtc, $dayEndUtc);
        $closures = $this->courts->closuresBetween($dayStartUtc, $dayEndUtc);
        $nowLocal = CarbonImmutable::now('UTC')->timezone($timezone);
        $courtSlots = [];
        $rangeStart = null;
        $rangeEnd = null;

        foreach ($courts as $court) {
            $hours = $court->operatingHours->firstWhere('day_of_week', $date->dayOfWeekIso);
            if (! $hours || $hours->is_closed) {
                $courtSlots[$court->id] = [];
                continue;
            }

            $opensAt = substr($hours->opens_at, 0, 5);
            $closesAt = substr($hours->closes_at, 0, 5);
            $rangeStart = $rangeStart === null ? $opensAt : min($rangeStart, $opensAt);
            $rangeEnd = $rangeEnd === null ? $closesAt : max($rangeEnd, $closesAt);
            $courtBookings = $bookings->where('court_id', $court->id);
            $courtClosures = $closures->where('court_id', $court->id);
            $cursor = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d').' '.$opensAt.':00', $timezone);
            $close = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d').' '.$closesAt.':00', $timezone);
            $courtSlots[$court->id] = [];

            while ($cursor->lt($close)) {
                $slotEnd = $cursor->addMinutes(30);
                $startsAt = $cursor->format('H:i');
                $endsAt = $slotEnd->format('H:i');
                $startsAtUtc = $cursor->utc();
                $endsAtUtc = $slotEnd->utc();
                $isClosed = $courtClosures->contains(fn (CourtClosure $closure): bool => $closure->starts_at->lt($endsAtUtc) && $closure->ends_at->gt($startsAtUtc));
                $isBooked = $courtBookings->contains(fn (Booking $booking): bool => $booking->starts_at->lt($endsAtUtc) && $booking->ends_at->gt($startsAtUtc));
                $isPast = $date->lt($nowLocal->startOfDay()) || ($date->isSameDay($nowLocal) && $cursor->lt($nowLocal));
                $status = $isClosed ? 'closed' : ($isBooked ? 'booked' : ($isPast ? 'past' : 'available'));

                $courtSlots[$court->id][] = [
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $status,
                    'amount' => $this->pricing->slotAmount($court, $date, $startsAt),
                ];
                $cursor = $slotEnd;
            }
        }

        $gridTimeSlots = [];
        if ($rangeStart !== null && $rangeEnd !== null) {
            $cursor = CarbonImmutable::createFromFormat('H:i', $rangeStart);
            $close = CarbonImmutable::createFromFormat('H:i', $rangeEnd);
            while ($cursor->lt($close)) {
                $slotEnd = $cursor->addMinutes(30);
                $gridTimeSlots[] = ['starts_at' => $cursor->format('H:i'), 'ends_at' => $slotEnd->format('H:i')];
                $cursor = $slotEnd;
            }
        }

        $slots = $selectedCourt ? ($courtSlots[$selectedCourt->id] ?? []) : [];

        return [
            'date' => $date->format('Y-m-d'),
            'today' => CarbonImmutable::now('UTC')->timezone($timezone)->format('Y-m-d'),
            'max_date' => CarbonImmutable::now($timezone)->startOfDay()->addDays(30)->format('Y-m-d'),
            'timezone' => $timezone,
            'selected_court_id' => $selectedCourt?->id,
            'courts' => $courts->map(fn (Court $court): array => $this->courtPayload($court, $date))->values()->all(),
            'slots' => $slots,
            'grid_time_slots' => $gridTimeSlots,
            'court_slots' => $courtSlots,
        ];
    }

    public function create(array $validated): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $validated['date'], $timezone);
        $this->validateBookingDate($date, $timezone);
        $activeCourts = $this->courts->allActiveForBooking()->keyBy('id');
        $now = CarbonImmutable::now($timezone);
        $items = [];
        $tokens = [];

        foreach ($validated['slots'] as $index => $slot) {
            $startsAtLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$slot['starts_at'], $timezone);
            $endsAtLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$slot['ends_at'], $timezone);
            if ($startsAtLocal->lt($now)) {
                throw ValidationException::withMessages(["slots.{$index}.starts_at" => 'Không thể đặt khung giờ đã qua.']);
            }

            $court = $activeCourts->get((int) $slot['court_id']);
            if (! $court) {
                throw ValidationException::withMessages(["slots.{$index}.court_id" => 'Sân không còn nhận đặt lịch.']);
            }

            $total = $this->pricing->rangeAmount($court, $startsAtLocal, $endsAtLocal);
            $token = Str::random(64);
            $items[] = [
                'court' => $court,
                'starts_at_utc' => $startsAtLocal->utc(),
                'ends_at_utc' => $endsAtLocal->utc(),
                'day_of_week' => $startsAtLocal->dayOfWeekIso,
                'local_starts_at' => $startsAtLocal->format('H:i'),
                'local_ends_at' => $endsAtLocal->format('H:i'),
                'attributes' => [
                    'booking_code' => 'BB-'.$startsAtLocal->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'status_token_hash' => hash('sha256', $token),
                    'customer_name' => trim($validated['customer_name']),
                    'customer_email' => strtolower($validated['customer_email']),
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'customer_note' => $validated['customer_note'] ?? null,
                    'base_amount' => $total,
                    'discount_amount' => '0.00',
                    'total_amount' => $total,
                    'currency' => $court->currency,
                    'payment_deadline_at' => null,
                ],
            ];
            $tokens[] = $token;
        }

        $bookings = $this->bookings->createConfirmedBatchForGuest($items);

        return ['bookings' => $bookings, 'status_tokens' => $tokens];
    }

    public function status(string $token): ?array
    {
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return null;
        }

        $booking = $this->bookings->findGuestStatusByTokenHash(hash('sha256', $token));
        if (! $booking) {
            return null;
        }

        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');

        return [
            'booking_code' => $booking->booking_code,
            'court' => $booking->court->name,
            'date' => $booking->starts_at->timezone($timezone)->format('Y-m-d'),
            'starts_at' => $booking->starts_at->timezone($timezone)->format('H:i'),
            'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
            'total_amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'status' => $booking->status,
        ];
    }

    private function validateBookingDate(CarbonImmutable $date, string $timezone): void
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        if ($date->lt($today) || $date->gt($today->addDays(30))) {
            throw ValidationException::withMessages(['date' => 'Vui lòng chọn ngày trong 30 ngày tới.']);
        }
    }

    private function courtPayload(Court $court, CarbonImmutable $date): array
    {
        $image = $court->images->firstWhere('is_primary', true) ?? $court->images->first();
        $path = $image?->image_path;
        $imageUrl = ! $path ? null : (preg_match('~^https?://~i', $path) || str_starts_with($path, '/') ? $path : '/storage/'.$path);
        $rules = $this->pricing->applicableRules($court, $date);
        $hourlyMinimum = $rules->isEmpty()
            ? $court->price_per_hour
            : $rules->min('price_per_hour');

        return [
            'id' => $court->id,
            'name' => $court->name,
            'description' => $court->description,
            'surface_type' => $court->surface_type,
            'capacity' => $court->capacity,
            'sort_order' => $court->sort_order,
            'price_per_hour' => (string) $hourlyMinimum,
            'currency' => $court->currency,
            'image_url' => $imageUrl,
        ];
    }
}
