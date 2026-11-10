<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Repositories\Contracts\AdminBookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EloquentAdminBookingRepository implements AdminBookingRepository
{
    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->filteredOrders($filters)
            ->with(['bookings.court', 'bookings.payments', 'bookings.statusHistory'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(array $filters): array
    {
        $orders = $this->filteredOrders($filters);
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $startOfToday = $today->utc();
        $startOfTomorrow = $today->addDay()->utc();
        $revenueToday = BookingOrder::query()
            ->where(fn ($orders) => $orders->where('status', 'PAID')
                ->orWhere(fn ($cancelled) => $cancelled->where('status', 'CANCELLED')->whereNotNull('paid_at')))
            ->where('created_at', '>=', $startOfToday)
            ->where('created_at', '<', $startOfTomorrow)
            ->selectRaw('currency, SUM(total_amount) as amount')
            ->groupBy('currency')
            ->get()
            ->map(fn ($row): array => ['currency' => $row->currency, 'amount' => (string) $row->amount])
            ->values();

        return [
            'total' => (clone $orders)->count(),
            'paid_bookings' => (clone $orders)->where('status', 'PAID')->count(),
            'pending' => (clone $orders)->where('status', 'PENDING_PAYMENT')->count(),
            'cancelled' => (clone $orders)->where('status', 'CANCELLED')->count(),
            'revenue_today' => $revenueToday,
        ];
    }

    public function findForAdmin(int $bookingId): Booking
    {
        return Booking::query()
            ->with(['court', 'payments', 'statusHistory', 'order.bookings.court', 'order.bookings.payments', 'order.bookings.statusHistory'])
            ->findOrFail($bookingId);
    }

    public function updateStatus(int $bookingId, string $status, int $userId, ?string $note): Booking
    {
        return DB::transaction(function () use ($bookingId, $status, $userId, $note): Booking {
            $snapshot = Booking::query()->with('order')->findOrFail($bookingId);
            $bookingIds = $snapshot->booking_order_id
                ? Booking::query()->where('booking_order_id', $snapshot->booking_order_id)->orderBy('id')->pluck('id')
                : collect([$snapshot->id]);
            $courtIds = Booking::query()->whereIn('id', $bookingIds)->distinct()->orderBy('court_id')->pluck('court_id');
            foreach ($courtIds as $courtId) Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();

            $order = BookingOrder::query()->whereKey($snapshot->booking_order_id)->lockForUpdate()->firstOrFail();
            $segments = Booking::query()->whereIn('id', $bookingIds)->orderBy('id')->lockForUpdate()->get();
            $currentStatus = $order->status;
            if (! in_array($status, ['PENDING_PAYMENT', 'PAID', 'CANCELLED'], true)) {
                throw ValidationException::withMessages(['status' => "Tr\u{1ea1}ng th\u{00e1}i \u{0111}\u{01a1}n kh\u{00f4}ng h\u{1ee3}p l\u{1ec7}."]);
            }
            if ($currentStatus === 'CANCELLED' && $status !== 'CANCELLED') {
                throw ValidationException::withMessages(['status' => 'Đơn đã hủy không thể được mở lại.']);
            }
            if ($currentStatus !== $status) {
                $updates = ['status' => $status];
                if ($status === 'PAID') {
                    $updates['paid_at'] = now();
                } elseif ($status === 'PENDING_PAYMENT') {
                    $updates['paid_at'] = null;
                }
                $order->forceFill($updates)->save();
                if (in_array($status, ['PAID', 'CANCELLED'], true)) {
                    DB::table('notifications')->whereIn('booking_id', $bookingIds)
                        ->where('channel', 'IN_APP')->where('type', 'BOOKING_PENDING_PAYMENT')->delete();
                }
                foreach ($segments as $segment) {
                    $segment->statusHistory()->create([
                        'old_status' => $currentStatus,
                        'new_status' => $status,
                        'changed_by' => $userId,
                        'change_source' => 'ADMIN',
                        'note' => $note ?: "Qu\u{1ea3}n tr\u{1ecb} vi\u{00ea}n c\u{1ead}p nh\u{1ead}t tr\u{1ea1}ng th\u{00e1}i \u{0111}\u{01a1}n.",
                    ]);
                }
            }

            return $segments->first()->refresh()->load(['court', 'payments', 'statusHistory', 'order.bookings.court', 'order.bookings.statusHistory']);
        });
    }

    public function courtsForFilter(): Collection
    {
        return Court::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    public function bulkUpdateStatus(array $orderIds, string $status, int $userId): int
    {
        return DB::transaction(function () use ($orderIds, $status, $userId): int {
            $orderIds = collect($orderIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values();
            $courtIds = Booking::query()->whereIn('booking_order_id', $orderIds)
                ->distinct()->orderBy('court_id')->pluck('court_id');
            foreach ($courtIds as $courtId) {
                Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
            }

            $orders = BookingOrder::query()->whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            if ($orders->count() !== $orderIds->count()) {
                throw ValidationException::withMessages(['booking_order_ids' => 'Một hoặc nhiều đơn không còn tồn tại.']);
            }
            if ($orders->contains(fn (BookingOrder $order): bool => $order->status === 'CANCELLED')) {
                throw ValidationException::withMessages(['booking_order_ids' => 'Đơn đã hủy không thể được cập nhật hàng loạt.']);
            }
            $segments = Booking::query()->whereIn('booking_order_id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($orders as $order) {
                if ($order->status === $status) continue;

                $oldStatus = $order->status;
                $order->forceFill(['status' => $status, 'paid_at' => $status === 'PAID' ? now() : null])->save();
                if ($status === 'PAID') {
                    DB::table('notifications')->whereIn('booking_id', $segments->where('booking_order_id', $order->id)->modelKeys())
                        ->where('channel', 'IN_APP')->where('type', 'BOOKING_PENDING_PAYMENT')->delete();
                }
                foreach ($segments->where('booking_order_id', $order->id) as $segment) {
                    $segment->statusHistory()->create([
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'changed_by' => $userId,
                        'change_source' => 'ADMIN',
                        'note' => 'Quản trị viên cập nhật trạng thái hàng loạt.',
                    ]);
                }
            }

            return $orders->count();
        });
    }

    public function bulkDelete(array $orderIds, int $userId): int
    {
        $proofPaths = [];
        $deletedCount = DB::transaction(function () use ($orderIds, $userId, &$proofPaths): int {
            $orderIds = collect($orderIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values();
            $courtIds = Booking::query()->whereIn('booking_order_id', $orderIds)
                ->distinct()->orderBy('court_id')->pluck('court_id');
            foreach ($courtIds as $courtId) {
                Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
            }

            $orders = BookingOrder::query()->whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            if ($orders->count() !== $orderIds->count()) {
                throw ValidationException::withMessages(['booking_order_ids' => 'Một hoặc nhiều đơn không còn tồn tại.']);
            }
            if ($orders->contains(fn (BookingOrder $order): bool => $order->status !== 'PENDING_PAYMENT')) {
                throw ValidationException::withMessages(['booking_order_ids' => 'Chỉ có thể xóa các đơn đang chờ thanh toán.']);
            }

            $segments = Booking::query()->whereIn('booking_order_id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            $segmentIds = $segments->modelKeys();
            $proofPaths = DB::table('payments')->whereIn('booking_id', $segmentIds)->whereNotNull('proof_image_path')->pluck('proof_image_path')->all();
            $paymentIds = DB::table('payments')->whereIn('booking_id', $segmentIds)->pluck('id');
            if (DB::table('bank_transfer_transactions')->whereIn('booking_id', $segmentIds)->orWhereIn('payment_id', $paymentIds)->exists()) {
                throw ValidationException::withMessages(['booking_order_ids' => 'Không thể xóa đơn đã được liên kết với giao dịch ngân hàng.']);
            }

            foreach ($orders as $order) {
                DB::table('audit_logs')->insert([
                    'user_id' => $userId,
                    'action' => 'booking.order_deleted',
                    'entity_type' => BookingOrder::class,
                    'entity_id' => (string) $order->id,
                    'before_data' => json_encode(['booking_code' => $order->booking_code, 'status' => $order->status, 'total_amount' => $order->total_amount], JSON_THROW_ON_ERROR),
                    'after_data' => null,
                    'created_at' => now(),
                ]);
            }

            DB::table('booking_status_history')->whereIn('booking_id', $segmentIds)->delete();
            DB::table('notifications')->whereIn('booking_id', $segmentIds)
                ->where('channel', 'IN_APP')->where('type', 'BOOKING_PENDING_PAYMENT')->delete();
            DB::table('payments')->whereIn('booking_id', $segmentIds)->delete();
            DB::table('bookings')->whereIn('id', $segmentIds)->delete();
            DB::table('booking_orders')->whereIn('id', $orderIds)->delete();

            return $orders->count();
        });

        foreach ($proofPaths as $path) {
            Storage::disk('local')->delete($path);
        }

        return $deletedCount;
    }

    public function notificationsForAdmin(int $userId, string $timezone): array
    {
        $notifications = DB::table('notifications as notices')
            ->join('bookings', 'bookings.id', '=', 'notices.booking_id')
            ->join('booking_orders as orders', 'orders.id', '=', 'bookings.booking_order_id')
            ->where('notices.user_id', $userId)
            ->where('notices.channel', 'IN_APP')
            ->where('notices.type', 'BOOKING_PENDING_PAYMENT')
            ->where('orders.status', 'PENDING_PAYMENT');

        $unreadCount = (clone $notifications)->whereNull('notices.read_at')->count('notices.id');
        $rows = (clone $notifications)
            ->select([
                'notices.id as notification_id',
                'notices.read_at',
                'notices.created_at as notified_at',
                'bookings.id as booking_id',
                'bookings.starts_at',
                'orders.id as booking_order_id',
                'orders.booking_code',
                'orders.customer_name',
                'orders.total_amount',
                'orders.currency',
            ])
            ->orderByDesc('notices.id')
            ->limit(25)
            ->get();

        $courtNames = DB::table('bookings as segments')
            ->join('courts', 'courts.id', '=', 'segments.court_id')
            ->whereIn('segments.booking_order_id', $rows->pluck('booking_order_id')->unique())
            ->orderBy('segments.starts_at')
            ->get(['segments.booking_order_id', 'courts.name as court_name'])
            ->groupBy('booking_order_id')
            ->map(fn ($segments): string => $segments->pluck('court_name')->unique()->implode(', '));

        return [
            'unread_count' => $unreadCount,
            'notifications' => $rows->map(fn ($row): array => [
                'id' => (int) $row->notification_id,
                'booking_id' => (int) $row->booking_id,
                'booking_order_id' => (int) $row->booking_order_id,
                'booking_code' => $row->booking_code,
                'customer_name' => $row->customer_name,
                'courts' => $courtNames->get($row->booking_order_id, ''),
                'starts_at' => CarbonImmutable::parse($row->starts_at, 'UTC')->timezone($timezone)->format('d/m/Y H:i'),
                'total_amount' => (string) $row->total_amount,
                'currency' => $row->currency,
                'created_at' => CarbonImmutable::parse($row->notified_at, 'UTC')->timezone($timezone)->format('d/m/Y H:i'),
                'read_at' => $row->read_at,
            ])->values()->all(),
        ];
    }

    public function markNotificationRead(int $notificationId, int $userId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->where('channel', 'IN_APP')
            ->where('type', 'BOOKING_PENDING_PAYMENT')
            ->whereNull('read_at')
            ->update(['read_at' => now('UTC'), 'updated_at' => now('UTC')]);
    }

    private function filteredOrders(array $filters)
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');

        return BookingOrder::query()
            ->whereHas('bookings')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['court_id'] ?? null, function ($query, int $courtId): void {
                $query->whereHas('bookings', fn ($segments) => $segments->where('court_id', $courtId));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(($filters['from'] ?? null) || ($filters['to'] ?? null), function ($query) use ($filters, $timezone): void {
                $start = ($filters['from'] ?? null)
                    ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], $timezone)->utc()
                    : null;
                $end = ($filters['to'] ?? null)
                    ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], $timezone)->addDay()->utc()
                    : null;
                $query->whereHas('bookings', function ($segments) use ($start, $end): void {
                    $segments->when($start, fn ($slots) => $slots->where('starts_at', '>=', $start))
                        ->when($end, fn ($slots) => $slots->where('starts_at', '<', $end));
                });
            });
    }

}
