<?php

namespace App\Services\Admin\Booking;

use App\Models\Booking;
use App\Repositories\Contracts\AdminBookingRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminBookingManagementService
{
    public function __construct(private readonly AdminBookingRepository $bookings) {}

    public function listing(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->bookings->paginateForAdmin($filters, $perPage);
    }

    public function stats(array $filters): array
    {
        return $this->bookings->stats($filters);
    }

    public function find(int $bookingId): Booking
    {
        return $this->bookings->findForAdmin($bookingId);
    }

    public function updateStatus(int $bookingId, string $status, int $userId, ?string $note): Booking
    {
        if (! in_array($status, ['PENDING_PAYMENT', 'PAID'], true)) {
            throw ValidationException::withMessages(['status' => "Tr\u{1ea1}ng th\u{00e1}i \u{0111}\u{01a1}n kh\u{00f4}ng h\u{1ee3}p l\u{1ec7}."]);
        }

        return $this->bookings->updateStatus($bookingId, $status, $userId, $note);
    }

    public function bulkUpdateStatus(array $orderIds, string $status, int $userId): int
    {
        if (! in_array($status, ['PENDING_PAYMENT', 'PAID'], true)) {
            throw ValidationException::withMessages(['status' => "Tr\u{1ea1}ng th\u{00e1}i \u{0111}\u{01a1}n kh\u{00f4}ng h\u{1ee3}p l\u{1ec7}."]);
        }

        return $this->bookings->bulkUpdateStatus($orderIds, $status, $userId);
    }

    public function bulkDelete(array $orderIds, int $userId): int
    {
        return $this->bookings->bulkDelete($orderIds, $userId);
    }

    public function paymentProofPath(int $bookingId, int $paymentId): ?string
    {
        $booking = $this->bookings->findForAdmin($bookingId);
        $payment = $booking->payments->firstWhere('id', $paymentId);

        return $payment?->proof_image_path;
    }

    public function courtsForFilter(): Collection
    {
        return $this->bookings->courtsForFilter();
    }

    public function notificationsForAdmin(int $userId, string $timezone): array
    {
        return $this->bookings->notificationsForAdmin($userId, $timezone);
    }

    public function markNotificationRead(int $notificationId, int $userId): void
    {
        $this->bookings->markNotificationRead($notificationId, $userId);
    }

}
