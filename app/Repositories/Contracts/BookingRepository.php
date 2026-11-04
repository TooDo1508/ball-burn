<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Court;
use Carbon\CarbonImmutable;

interface BookingRepository
{
    /** @param array<int, array<string, mixed>> $items
     *  @return array<int, Booking>
     */
    public function createConfirmedBatchForGuest(array $items): array;

    public function createConfirmedForGuest(
        Court $court,
        CarbonImmutable $startsAtUtc,
        CarbonImmutable $endsAtUtc,
        int $dayOfWeek,
        string $localStartsAt,
        string $localEndsAt,
        array $attributes,
    ): Booking;

    public function findGuestStatusByTokenHash(string $tokenHash): ?Booking;

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
