<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAdminBookingsRequest;
use App\Http\Requests\Admin\BulkDeleteAdminBookingsRequest;
use App\Http\Requests\Admin\BulkUpdateAdminBookingStatusRequest;
use App\Http\Requests\Admin\UpdateAdminBookingStatusRequest;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Services\Admin\Booking\AdminBookingManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $bookings = $this->bookings->listing($filters, $perPage)->through(function (BookingOrder $order) use ($timezone): array {
            $segments = $order->bookings;
            $booking = $segments->first();
            $slots = $segments->map(fn (Booking $segment): array => [
                'court' => $segment->court->name,
                'starts_at' => $segment->starts_at->timezone($timezone)->format('d/m/Y H:i'),
                'ends_at' => $segment->ends_at->timezone($timezone)->format('H:i'),
            ])->values();

            return [
                'id' => $booking->id,
                'order_id' => $order->id,
                'booking_code' => $order->booking_code,
                'court' => $slots->pluck('court')->unique()->implode(', '),
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'starts_at' => $slots->first()['starts_at'] ?? '',
                'ends_at' => $slots->last()['ends_at'] ?? '',
                'slots' => $slots,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $order->status,
                'payment_deadline_at' => $order->payment_deadline_at?->timezone($timezone)->format('d/m/Y H:i:s'),
                'created_at' => $order->created_at?->timezone($timezone)->format('d/m/Y H:i'),
                'detail' => $this->bookingDetails($booking, $timezone),
            ];
        });

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings,
            'stats' => $this->bookings->stats($filters),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'court_id' => $filters['court_id'] ?? '',
                'status' => $filters['status'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'per_page' => $perPage,
            ],
            'courts' => $this->bookings->courtsForFilter(),
        ]);
    }

    public function cancellations(IndexAdminBookingsRequest $request): Response
    {
        return $this->index($request);
    }

    public function show(int $bookingId): Response
    {
        $this->authorize('viewAny', Booking::class);
        $booking = $this->bookings->find($bookingId);
        $this->authorize('view', $booking);
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        return Inertia::render('admin/bookings/Show', [
            'booking' => $this->bookingDetails($booking, $timezone),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->bookings->notificationsForAdmin(
                (int) $request->user()->id,
                (string) config('venue.timezone', 'Asia/Ho_Chi_Minh'),
            ),
        ]);
    }

    public function markNotificationRead(Request $request, int $notificationId): JsonResponse
    {
        $this->bookings->markNotificationRead($notificationId, (int) $request->user()->id);

        return response()->json(['data' => ['read' => true]]);
    }

    private function bookingDetails(Booking $booking, string $timezone): array
    {
        $segments = $booking->order?->bookings ?? collect([$booking]);
        $slots = $segments->map(fn (Booking $segment): array => [
            'court' => $segment->court->name,
            'starts_at' => $segment->starts_at->timezone($timezone)->format('d/m/Y H:i'),
            'ends_at' => $segment->ends_at->timezone($timezone)->format('d/m/Y H:i'),
            'amount' => $segment->total_amount,
        ])->values();
        $history = $segments->flatMap(fn (Booking $segment) => $segment->statusHistory)->sortBy('created_at')->values();
        $payments = $segments->flatMap(fn (Booking $segment) => $segment->payments)->unique('id')->sortByDesc('created_at')->values();
        $status = $booking->order->status;
        $deadline = $booking->order->payment_deadline_at;

        return [
            'id' => $booking->id,
            'booking_code' => $booking->order?->booking_code ?? $booking->booking_code,
            'status' => $status,
            'status_options' => $this->statusOptions($status),
            'court' => $slots->pluck('court')->unique()->implode(', '),
            'slots' => $slots,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'customer_note' => $booking->customer_note,
            'payment_deadline_at' => $deadline?->timezone($timezone)->format('d/m/Y H:i:s'),
            'base_amount' => $booking->order?->base_amount ?? $booking->base_amount,
            'discount_amount' => $booking->order?->discount_amount ?? $booking->discount_amount,
            'total_amount' => $booking->order?->total_amount ?? $booking->total_amount,
            'currency' => $booking->order?->currency ?? $booking->currency,
            'payments' => $payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'booking_id' => $payment->booking_id,
                'provider' => $payment->provider,
                'provider_transaction_id' => $payment->provider_transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'payment_reference' => $payment->provider_metadata['payment_reference'] ?? null,
                'proof_url' => $payment->proof_image_path
                    ? route('admin.bookings.payment-proof', ['bookingId' => $payment->booking_id, 'paymentId' => $payment->id])
                    : null,
                'paid_at' => $payment->paid_at?->timezone($timezone)->format('d/m/Y H:i:s'),
            ])->values(),
            'history' => $history->map(fn ($item): array => [
                'old_status' => $item->old_status,
                'new_status' => $item->new_status,
                'change_source' => $item->change_source,
                'note' => $item->note,
                'created_at' => $item->created_at?->timezone($timezone)->format('d/m/Y H:i:s'),
            ])->values(),
        ];
    }

    public function updateStatus(UpdateAdminBookingStatusRequest $request, int $bookingId): RedirectResponse
    {
        $booking = $this->bookings->find($bookingId);
        $this->authorize('updateStatus', $booking);
        $validated = $request->validated();

        $this->bookings->updateStatus(
            $bookingId,
            $validated['status'],
            (int) $request->user()->id,
            $validated['note'] ?? null,
        );

        return back()->with('success', 'Đã cập nhật trạng thái đơn đặt sân.');
    }

    public function bulkUpdateStatus(BulkUpdateAdminBookingStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $bookings = Booking::query()->whereIn('booking_order_id', $data['booking_order_ids'])->get();
        foreach ($bookings as $booking) $this->authorize('updateStatus', $booking);

        $count = $this->bookings->bulkUpdateStatus(
            $data['booking_order_ids'],
            $data['status'],
            (int) $request->user()->id,
        );

        return back()->with('success', "Đã cập nhật trạng thái cho {$count} đơn.");
    }

    public function bulkDelete(BulkDeleteAdminBookingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $bookings = Booking::query()->whereIn('booking_order_id', $data['booking_order_ids'])->get();
        foreach ($bookings as $booking) $this->authorize('delete', $booking);

        $count = $this->bookings->bulkDelete($data['booking_order_ids'], (int) $request->user()->id);

        return back()->with('success', "Đã xóa {$count} đơn đang chờ thanh toán.");
    }

    private function statusOptions(string $status): array
    {
        if ($status === 'CANCELLED') {
            return [];
        }

        $options = $status === 'PENDING_PAYMENT'
            ? [['value' => 'PAID', 'label' => "\u{0110}\u{00e3} thanh to\u{00e1}n"]]
            : [['value' => 'PENDING_PAYMENT', 'label' => "Ch\u{1edd} thanh to\u{00e1}n"]];

        $options[] = ['value' => 'CANCELLED', 'label' => 'Hủy sân'];

        return $options;
    }

    public function paymentProof(int $bookingId, int $paymentId)
    {
        $this->authorize('viewAny', Booking::class);
        $booking = $this->bookings->find($bookingId);
        $this->authorize('view', $booking);
        $path = $this->bookings->paymentProofPath($bookingId, $paymentId);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

}
