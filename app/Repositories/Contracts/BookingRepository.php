<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Court;
use Carbon\CarbonImmutable;

interface BookingRepository
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
    ): Booking;
}
