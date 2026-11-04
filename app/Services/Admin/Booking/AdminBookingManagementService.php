<?php

namespace App\Services\Admin\Booking;

use App\Models\BankTransferTransaction;
use App\Models\Booking;
use App\Repositories\Contracts\AdminBookingRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    public function unmatchedTransfers(): Collection
    {
        return $this->bookings->unmatchedTransfers();
    }

    public function matchableBookings(): Collection
    {
        return $this->bookings->matchableBookings();
    }

    public function courtsForFilter(): Collection
    {
        return $this->bookings->courtsForFilter();
    }

    public function recordTransfer(array $attributes, int $userId): BankTransferTransaction
    {
        return $this->bookings->recordBankTransfer($attributes, $userId);
    }

    public function matchTransfer(int $transactionId, int $bookingId, int $userId, ?string $note): BankTransferTransaction
    {
        return $this->bookings->matchBankTransfer($transactionId, $bookingId, $userId, $note);
    }
}
