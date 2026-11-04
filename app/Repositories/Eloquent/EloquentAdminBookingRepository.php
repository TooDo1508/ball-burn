<?php

namespace App\Repositories\Eloquent;

use App\Models\BankTransferTransaction;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Repositories\Contracts\AdminBookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EloquentAdminBookingRepository implements AdminBookingRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->filteredBookings($filters)
            ->with(['court', 'payments'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(array $filters): array
    {
        $bookings = $this->filteredBookings($filters);
        $rows = (clone $bookings)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $paymentRows = (clone $bookings)
            ->join('payments', 'payments.booking_id', '=', 'bookings.id')
            ->where('payments.status', 'PAID')
            ->selectRaw('COUNT(DISTINCT bookings.id) as paid_bookings, COALESCE(SUM(payments.amount), 0) as paid_amount')
            ->first();

        return [
            'total' => (int) $rows->sum(),
            'confirmed' => (int) ($rows['CONFIRMED'] ?? 0),
            'pending' => (int) ($rows['PENDING_PAYMENT'] ?? 0),
            'cancelled' => (int) ($rows['CANCELLED'] ?? 0),
            'expired' => (int) ($rows['EXPIRED'] ?? 0),
            'paid_bookings' => (int) ($paymentRows->paid_bookings ?? 0),
            'paid_amount' => (string) ($paymentRows->paid_amount ?? '0.00'),
        ];
    }

    public function findForAdmin(int $bookingId): Booking
    {
        return Booking::query()
            ->with(['court', 'payments', 'statusHistory'])
            ->findOrFail($bookingId);
    }

    public function unmatchedTransfers(): Collection
    {
        return BankTransferTransaction::query()
            ->with('booking')
            ->whereIn('status', ['UNMATCHED', 'NEEDS_REVIEW', 'LATE_PAYMENT'])
            ->latest('transaction_at')
            ->limit(100)
            ->get();
    }

    public function matchableBookings(): Collection
    {
        return Booking::query()
            ->whereIn('status', ['PENDING_PAYMENT', 'CONFIRMED', 'COMPLETED', 'EXPIRED', 'CANCELLED'])
            ->latest('created_at')
            ->limit(100)
            ->get(['id', 'booking_code', 'customer_name', 'court_id', 'starts_at', 'status', 'total_amount', 'currency']);
    }

    public function courtsForFilter(): Collection
    {
        return Court::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    public function recordBankTransfer(array $attributes, int $userId): BankTransferTransaction
    {
        return DB::transaction(function () use ($attributes, $userId): BankTransferTransaction {
            $transaction = BankTransferTransaction::query()->create([
                ...$attributes,
                'bank_transaction_id' => trim($attributes['bank_transaction_id']),
                'currency' => strtoupper($attributes['currency']),
                'transaction_at' => CarbonImmutable::parse($attributes['transaction_at'], (string) config('venue.timezone'))->utc(),
                'status' => 'UNMATCHED',
                'recorded_by' => $userId,
            ]);

            $this->audit($userId, 'bank_transfer.recorded', $transaction, null, [
                'bank_transaction_id' => $transaction->bank_transaction_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'transaction_at' => $transaction->transaction_at?->toISOString(),
            ]);

            return $transaction;
        });
    }

    public function matchBankTransfer(int $transactionId, int $bookingId, int $userId, ?string $note): BankTransferTransaction
    {
        return DB::transaction(function () use ($transactionId, $bookingId, $userId, $note): BankTransferTransaction {
            $bookingSnapshot = Booking::query()->findOrFail($bookingId);
            Court::query()->whereKey($bookingSnapshot->court_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($bookingId)->lockForUpdate()->firstOrFail();
            $transaction = BankTransferTransaction::query()->whereKey($transactionId)->lockForUpdate()->firstOrFail();

            if (! in_array($transaction->status, ['UNMATCHED', 'NEEDS_REVIEW'], true) || $transaction->payment_id !== null) {
                throw ValidationException::withMessages(['booking_id' => 'Giao dịch này đã được xử lý hoặc gắn với một khoản thanh toán.']);
            }

            if ($transaction->currency !== $booking->currency || $this->money($transaction->amount) !== $this->money($booking->total_amount)) {
                $transaction->forceFill([
                    'booking_id' => null,
                    'status' => 'NEEDS_REVIEW',
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'review_note' => 'Ứng viên '.$booking->booking_code.': '.($note ?: 'Số tiền hoặc tiền tệ không khớp với tổng tiền booking.'),
                ])->save();
                $this->audit($userId, 'bank_transfer.needs_review', $transaction, null, [
                    'candidate_booking_id' => $booking->id,
                    'reason' => 'amount_or_currency_mismatch',
                ]);

                return $transaction->refresh()->load('booking');
            }

            if ($booking->payments()->where('status', 'PAID')->exists()) {
                throw ValidationException::withMessages(['booking_id' => 'Booking này đã có khoản thanh toán được ghi nhận.']);
            }

            $deadlinePassed = $booking->status === 'PENDING_PAYMENT'
                && (! $booking->payment_deadline_at || $booking->payment_deadline_at->lte(now()));
            $overlap = Booking::query()
                ->where('court_id', $booking->court_id)
                ->where('id', '<>', $booking->id)
                ->where('starts_at', '<', $booking->ends_at)
                ->where('ends_at', '>', $booking->starts_at)
                ->where(function ($query): void {
                    $query->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                        ->orWhere(function ($query): void {
                            $query->where('status', 'PENDING_PAYMENT')->where('payment_deadline_at', '>', now());
                        });
                })
                ->exists();

            $late = $deadlinePassed || $overlap || in_array($booking->status, ['EXPIRED', 'CANCELLED'], true);
            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'provider' => 'BANK_TRANSFER',
                'provider_transaction_id' => $transaction->bank_transaction_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => 'PAID',
                'payment_method' => 'BANK_TRANSFER',
                'provider_metadata' => [
                    'transfer_description' => $transaction->transfer_description,
                    'recorded_by' => $userId,
                    'review_note' => $note,
                ],
                'paid_at' => $transaction->transaction_at,
            ]);

            if ($deadlinePassed && $booking->status === 'PENDING_PAYMENT') {
                $booking->forceFill(['status' => 'EXPIRED'])->save();
                $booking->statusHistory()->create([
                    'old_status' => 'PENDING_PAYMENT',
                    'new_status' => 'EXPIRED',
                    'changed_by' => $userId,
                    'change_source' => 'ADMIN',
                    'note' => 'Payment deadline passed before manual bank reconciliation.',
                ]);
            } elseif (! $late && $booking->status === 'PENDING_PAYMENT') {
                $booking->forceFill(['status' => 'CONFIRMED', 'confirmed_at' => now()])->save();
                $booking->statusHistory()->create([
                    'old_status' => 'PENDING_PAYMENT',
                    'new_status' => 'CONFIRMED',
                    'changed_by' => $userId,
                    'change_source' => 'ADMIN',
                    'note' => 'Bank transfer manually verified against the bank transaction.',
                ]);
            }

            $transaction->forceFill([
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'status' => $late ? 'LATE_PAYMENT' : 'MATCHED',
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();
            $this->audit($userId, $late ? 'bank_transfer.late_payment' : 'bank_transfer.matched', $transaction, null, [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'status' => $transaction->status,
            ]);

            return $transaction->refresh()->load('booking');
        });
    }

    private function filteredBookings(array $filters)
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');

        return Booking::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['court_id'] ?? null, fn ($query, int $courtId) => $query->where('court_id', $courtId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, function ($query, string $status): void {
                if ($status === 'UNPAID') {
                    $query->whereDoesntHave('payments', fn ($payments) => $payments->where('status', 'PAID'));
                } else {
                    $query->whereHas('payments', fn ($payments) => $payments->where('status', $status));
                }
            })
            ->when($filters['from'] ?? null, function ($query, string $from) use ($timezone): void {
                $start = CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone)->utc();
                $query->where('starts_at', '>=', $start);
            })
            ->when($filters['to'] ?? null, function ($query, string $to) use ($timezone): void {
                $end = CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->addDay()->utc();
                $query->where('starts_at', '<', $end);
            });
    }

    private function money(string|int|float|null $value): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $parts)) {
            return '';
        }

        $whole = ltrim($parts[1], '0') ?: '0';
        $fraction = str_pad($parts[2] ?? '', 2, '0');

        return $whole.'.'.$fraction;
    }

    private function audit(int $userId, string $action, BankTransferTransaction $transaction, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => BankTransferTransaction::class,
            'entity_id' => (string) $transaction->id,
            'before_data' => $before ? json_encode($before) : null,
            'after_data' => $after ? json_encode($after) : null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
