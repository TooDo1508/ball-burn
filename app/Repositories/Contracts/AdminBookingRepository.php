<?php

namespace App\Repositories\Contracts;

use App\Models\BankTransferTransaction;
use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminBookingRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function stats(array $filters): array;

    public function findForAdmin(int $bookingId): Booking;

    public function unmatchedTransfers(): Collection;

    public function matchableBookings(): Collection;

    public function courtsForFilter(): Collection;

    public function matchBankTransfer(int $transactionId, int $bookingId, int $userId, ?string $note): BankTransferTransaction;

    public function recordBankTransfer(array $attributes, int $userId): BankTransferTransaction;
}
