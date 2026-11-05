<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Court;
use Carbon\CarbonImmutable;

interface BookingRepository
{
    /** @param array<int, array<string, mixed>> $items */
    public function guestPaymentInstructions(array $items, string $holdTokenHash): array;

    public function createPendingBatchForGuest(array $items, array $orderAttributes, string $paymentReference, string $holdTokenHash, string $proofImagePath): BookingOrder;

    /** @param array<int, array<string, mixed>> $slots */
    public function syncGuestHolds(string $tokenHash, array $slots): array;

    public function releaseGuestHolds(string $tokenHash): void;

    public function findGuestStatusByTokenHash(string $tokenHash): ?BookingOrder;

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
