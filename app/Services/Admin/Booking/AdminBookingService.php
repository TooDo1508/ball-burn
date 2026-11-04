<?php

namespace App\Services\Admin\Booking;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepository;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Court\CourtPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminBookingService
{
    public function __construct(
        private readonly CourtRepository $courts,
        private readonly BookingRepository $bookings,
        private readonly CourtPricingService $pricing,
    ) {}

    public function create(array $validated, int $userId): Booking
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $validated['date'], $timezone);
        $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['starts_at'], $timezone);
        $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['ends_at'], $timezone);
        $today = CarbonImmutable::now($timezone)->startOfDay();
        if ($date->lt($today) || $date->gt($today->addDays(30))) {
            throw ValidationException::withMessages(['date' => 'Admin chỉ có thể đặt sân trong 30 ngày tới.']);
        }
        if ($startsAt->lt(CarbonImmutable::now($timezone))) {
            throw ValidationException::withMessages(['starts_at' => 'Không thể tạo booking trong quá khứ.']);
        }

        $court = $this->courts->findForAdmin((int) $validated['court_id']);
        $amount = $this->pricing->rangeAmount($court, $startsAt, $endsAt);

        return $this->bookings->createConfirmedForAdmin(
            $court,
            $startsAt->utc(),
            $endsAt->utc(),
            $startsAt->dayOfWeekIso,
            $startsAt->format('H:i'),
            $endsAt->format('H:i'),
            [
                'booking_code' => 'BB-'.$startsAt->format('Ymd').'-'.Str::upper(Str::random(6)),
                'status_token_hash' => hash('sha256', Str::random(64)),
                'customer_name' => trim($validated['customer_name']),
                'customer_email' => strtolower($validated['customer_email']),
                'customer_phone' => $validated['customer_phone'] ?? null,
                'base_amount' => $amount,
                'discount_amount' => '0.00',
                'total_amount' => $amount,
                'currency' => $court->currency,
                'customer_note' => $validated['customer_note'] ?? null,
                'payment_deadline_at' => null,
            ],
            $userId,
        );
    }
}
