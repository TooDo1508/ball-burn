<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminBookingRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function stats(array $filters): array;

    public function findForAdmin(int $bookingId): Booking;

    public function updateStatus(int $bookingId, string $status, int $userId, ?string $note): Booking;

    /** @param array<int, int> $orderIds */
    public function bulkUpdateStatus(array $orderIds, string $status, int $userId): int;

    /** @param array<int, int> $orderIds */
    public function bulkDelete(array $orderIds, int $userId): int;

    /** @return array{unread_count: int, notifications: array<int, array<string, mixed>>} */
    public function notificationsForAdmin(int $userId, string $timezone): array;

    public function markNotificationRead(int $notificationId, int $userId): void;

    public function courtsForFilter(): Collection;
}
