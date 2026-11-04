<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAdminBookingsRequest;
use App\Http\Requests\Admin\MatchBankTransferRequest;
use App\Http\Requests\Admin\StoreBankTransferTransactionRequest;
use App\Models\Booking;
use App\Services\Admin\Booking\AdminBookingManagementService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingManagementController extends Controller
{
    public function __construct(private readonly AdminBookingManagementService $bookings) {}

    public function index(IndexAdminBookingsRequest $request): Response
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 10);
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $bookings = $this->bookings->listing($filters, $perPage)->through(function (Booking $booking) use ($timezone): array {
            $latestPayment = $booking->payments->first();

            return [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'court' => $booking->court->name,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'starts_at' => $booking->starts_at->timezone($timezone)->format('d/m/Y H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'status' => $booking->status,
                'payment_status' => $booking->payments->contains(fn ($payment): bool => $payment->status === 'PAID')
                    ? 'PAID'
                    : ($latestPayment?->status ?? 'UNPAID'),
                'payment_deadline_at' => $booking->payment_deadline_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                'created_at' => $booking->created_at?->timezone($timezone)->format('d/m/Y H:i'),
            ];
        });

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings,
            'stats' => $this->bookings->stats($filters),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'court_id' => $filters['court_id'] ?? '',
                'status' => $filters['status'] ?? '',
                'payment_status' => $filters['payment_status'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'per_page' => $perPage,
            ],
            'courts' => $this->bookings->courtsForFilter(),
            'transfers' => $this->bookings->unmatchedTransfers()->map(fn ($transfer): array => [
                'id' => $transfer->id,
                'bank_transaction_id' => $transfer->bank_transaction_id,
                'amount' => $transfer->amount,
                'currency' => $transfer->currency,
                'transaction_at' => $transfer->transaction_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                'transfer_description' => $transfer->transfer_description,
                'status' => $transfer->status,
                'booking_code' => $transfer->booking?->booking_code,
                'review_note' => $transfer->review_note,
            ])->values(),
            'matchableBookings' => $this->bookings->matchableBookings()->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'label' => $booking->booking_code.' · '.$booking->customer_name.' · '.$booking->total_amount.' '.$booking->currency.' · '.$booking->status,
            ])->values(),
        ]);
    }

    public function show(int $bookingId): Response
    {
        $this->authorize('viewAny', Booking::class);
        $booking = $this->bookings->find($bookingId);
        $this->authorize('view', $booking);
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');

        return Inertia::render('admin/bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'court' => $booking->court->name,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'customer_note' => $booking->customer_note,
                'starts_at' => $booking->starts_at->timezone($timezone)->format('d/m/Y H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('d/m/Y H:i'),
                'payment_deadline_at' => $booking->payment_deadline_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                'base_amount' => $booking->base_amount,
                'discount_amount' => $booking->discount_amount,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'payments' => $booking->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'provider_transaction_id' => $payment->provider_transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->paid_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                ])->values(),
                'history' => $booking->statusHistory->map(fn ($history): array => [
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'change_source' => $history->change_source,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                ])->values(),
            ],
        ]);
    }

    public function storeTransfer(StoreBankTransferTransactionRequest $request): RedirectResponse
    {
        $this->authorize('reconcilePayment', Booking::class);
        $transfer = $this->bookings->recordTransfer($request->validated(), (int) $request->user()->id);

        return back()->with('success', "Đã ghi nhận giao dịch {$transfer->bank_transaction_id} vào hàng đợi đối soát.");
    }

    public function matchTransfer(MatchBankTransferRequest $request, int $transactionId): RedirectResponse
    {
        $this->authorize('reconcilePayment', Booking::class);
        $transfer = $this->bookings->matchTransfer(
            $transactionId,
            (int) $request->validated('booking_id'),
            (int) $request->user()->id,
            $request->validated('review_note'),
        );
        $message = $transfer->status === 'MATCHED'
            ? "Đã đối soát và xác nhận booking {$transfer->booking->booking_code}."
            : "Giao dịch đã được ghi nhận nhưng cần xử lý ngoại lệ ({$transfer->status}).";

        return back()->with('success', $message);
    }
}
