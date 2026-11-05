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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuestBookingService
{
    public function __construct(
        private readonly CourtRepository $courts,
        private readonly BookingRepository $bookings,
        private readonly CourtPricingService $pricing,
    ) {}

    public function availability(string $dateValue, ?int $courtId = null, ?string $holdToken = null): array
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
        $holds = $this->courts->holdsBetween($dayStartUtc, $dayEndUtc, $holdToken ? hash('sha256', $holdToken) : null);
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
            $courtHolds = $holds->where('court_id', $court->id);
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
                $isBooked = $courtBookings->contains(fn (Booking $booking): bool => $booking->starts_at->lt($endsAtUtc) && $booking->ends_at->gt($startsAtUtc))
                    || $courtHolds->contains(fn ($hold): bool => $hold->starts_at->lt($endsAtUtc) && $hold->ends_at->gt($startsAtUtc));
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

    public function syncHolds(string $dateValue, array $slots, ?string $token = null): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateValue, $timezone);
        $this->validateBookingDate($date, $timezone);
        $token ??= Str::random(64);
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) throw ValidationException::withMessages(['hold_token' => 'Mã giữ chỗ không hợp lệ.']);
        $items = [];
        foreach ($slots as $i => $slot) {
            $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $dateValue.' '.$slot['starts_at'], $timezone);
            $end = CarbonImmutable::createFromFormat('Y-m-d H:i', $dateValue.' '.$slot['ends_at'], $timezone);
            if ((int) $start->diffInMinutes($end) !== 30 || $start->lt(CarbonImmutable::now($timezone))) throw ValidationException::withMessages(["slots.{$i}" => 'Mỗi lượt giữ chỗ phải là khung 30 phút trong tương lai.']);
            $items[] = ['court_id' => (int) $slot['court_id'], 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'day_of_week' => $start->dayOfWeekIso, 'local_starts_at' => $start->format('H:i'), 'local_ends_at' => $end->format('H:i')];
        }
        $hold = $this->bookings->syncGuestHolds(hash('sha256', $token), $items);
        return [
            'token' => $token,
            'expires_at' => $hold['expires_at']->toISOString(),
            'payment_reference' => $hold['payment_reference'],
        ];
    }

    public function releaseHolds(string $token): void
    {
        if (preg_match('/^[A-Za-z0-9]{64}$/', $token)) $this->bookings->releaseGuestHolds(hash('sha256', $token));
    }

    public function paymentInstructions(array $validated): array
    {
        $context = $this->guestOrderContext($validated);
        $hold = $this->bookings->guestPaymentInstructions($context['items'], hash('sha256', $validated['hold_token']));
        return [
            'payment_reference' => $hold['payment_reference'],
            'payment_deadline_at' => $hold['expires_at'],
            'total_amount' => $context['total_amount'],
            'currency' => $context['currency'],
        ];
    }

    public function createWithPaymentProof(array $validated): array
    {
        $path = $validated['proof_image']->store('booking-payment-proofs', 'local');

        if (! $path) {
            throw ValidationException::withMessages(['proof_image' => 'Không thể lưu ảnh chuyển khoản. Vui lòng thử lại.']);
        }

        try {
            $context = $this->guestOrderContext($validated);
            $holdTokenHash = hash('sha256', $validated['hold_token']);
            $statusToken = $validated['status_token'];
            $order = $this->bookings->createPendingBatchForGuest($context['items'], [
                'booking_code' => 'BB-'.$context['date']->format('Ymd').'-'.Str::upper(Str::random(6)),
                'status_token_hash' => hash('sha256', $statusToken),
                'hold_token_hash' => $holdTokenHash,
                'customer_name' => trim($validated['customer_name']),
                'customer_email' => isset($validated['customer_email']) && trim($validated['customer_email']) !== ''
                    ? strtolower(trim($validated['customer_email']))
                    : null,
                'customer_phone' => trim($validated['customer_phone']),
                'customer_note' => $validated['customer_note'] ?? null,
                'base_amount' => $context['total_amount'],
                'discount_amount' => '0.00',
                'total_amount' => $context['total_amount'],
                'currency' => $context['currency'],
            ], $validated['payment_reference'], $holdTokenHash, $path);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $proofWasStored = $order->bookings->flatMap(fn (Booking $segment) => $segment->payments)
            ->contains(fn ($payment): bool => $payment->proof_image_path === $path);
        if (! $proofWasStored) Storage::disk('local')->delete($path);

        return ['order' => $order, 'status_token' => $statusToken];
    }

    /**
     * Build the server-priced booking segments shared by payment preview and
     * final order creation. No order is written until a proof image is sent.
     *
     * @return array{items: array<int, array<string, mixed>>, total_amount: string, currency: string, date: CarbonImmutable}
     */
    private function guestOrderContext(array $validated): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $validated['date'], $timezone);
        $this->validateBookingDate($date, $timezone);
        $activeCourts = $this->courts->allActiveForBooking()->keyBy('id');
        $now = CarbonImmutable::now($timezone);
        $items = [];

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
            $items[] = [
                'court' => $court,
                'starts_at_utc' => $startsAtLocal->utc(),
                'ends_at_utc' => $endsAtLocal->utc(),
                'day_of_week' => $startsAtLocal->dayOfWeekIso,
                'local_starts_at' => $startsAtLocal->format('H:i'),
                'local_ends_at' => $endsAtLocal->format('H:i'),
                'attributes' => [
                    'base_amount' => $total,
                    'discount_amount' => '0.00',
                    'total_amount' => $total,
                    'currency' => $court->currency,
                ],
            ];
        }

        $currencies = collect($items)->map(fn (array $item): string => $item['attributes']['currency'])->unique();
        if ($currencies->count() !== 1) {
            throw ValidationException::withMessages(['slots' => 'Các khung giờ trong cùng một đơn phải dùng chung loại tiền tệ.']);
        }

        $totalCents = collect($items)->sum(fn (array $item): int => $this->moneyToCents((string) $item['attributes']['total_amount']));

        return [
            'items' => $items,
            'total_amount' => number_format($totalCents / 100, 2, '.', ''),
            'currency' => $currencies->first(),
            'date' => $date,
        ];
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
            'bookings' => $booking->bookings->map(fn (Booking $segment): array => [
                'court' => $segment->court->name,
                'date' => $segment->starts_at->timezone($timezone)->format('Y-m-d'),
                'starts_at' => $segment->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $segment->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $segment->total_amount,
                'currency' => $segment->currency,
                'status' => $booking->status,
            ])->values(),
            'total_amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'status' => $booking->currentStatus(),
            'payment_deadline_at' => $booking->payment_deadline_at?->toISOString(),
            'payment_submitted' => $booking->bookings->flatMap(fn (Booking $segment) => $segment->payments)->contains(fn ($payment): bool => filled($payment->proof_image_path)),
        ];
    }

    private function validateBookingDate(CarbonImmutable $date, string $timezone): void
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        if ($date->lt($today) || $date->gt($today->addDays(30))) {
            throw ValidationException::withMessages(['date' => 'Vui lòng chọn ngày trong 30 ngày tới.']);
        }
    }

    private function moneyToCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) substr(str_pad($fraction, 2, '0'), 0, 2);
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
