<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\CourtOperatingHour;
use App\Models\Payment;
use App\Repositories\Contracts\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EloquentBookingRepository implements BookingRepository
{
    public function syncGuestHolds(string $tokenHash, array $slots): array
    {
        return DB::transaction(function () use ($tokenHash, $slots): array {
            $courtIds = collect($slots)->pluck('court_id')->unique()->sort()->values();
            $lockedCourts = [];
            foreach ($courtIds as $id) {
                $lockedCourts[$id] = Court::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            }
            $existing = BookingHold::query()->where('token_hash', $tokenHash)->lockForUpdate()->get();
            $existingExpiry = $existing->first()?->expires_at;
            $expires = $existingExpiry ? CarbonImmutable::instance($existingExpiry) : CarbonImmutable::now('UTC')->addMinutes(5);
            $paymentReference = $existing->pluck('payment_reference')->filter()->first() ?? 'BBPAY'.Str::upper(Str::random(10));
            if ($expires->lte(now('UTC'))) {
                throw ValidationException::withMessages(['slots' => 'Thời gian giữ chỗ đã hết. Vui lòng chọn lại.']);
            }
            BookingHold::query()->where('token_hash', $tokenHash)->delete();
            foreach ($slots as $slot) {
                $courtId = (int) $slot['court_id'];
                $court = $lockedCourts[$courtId] ?? null;
                if (! $court || ! $court->is_active) {
                    throw ValidationException::withMessages(['slots' => 'Sân không còn nhận đặt lịch.']);
                }
                $start = $slot['starts_at'];
                $end = $slot['ends_at'];
                $hours = CourtOperatingHour::query()->where('court_id', $courtId)->where('day_of_week', $slot['day_of_week'])->where('is_closed', false)->first();
                if (! $hours || $slot['local_starts_at'] < substr($hours->opens_at, 0, 5) || $slot['local_ends_at'] > substr($hours->closes_at, 0, 5)) {
                    throw ValidationException::withMessages(['slots' => 'Khung giữ chỗ phải nằm trong giờ hoạt động của sân.']);
                }
                $bookingExists = Booking::query()->where('court_id', $courtId)->where('starts_at', '<', $end)->where('ends_at', '>', $start)
                    ->occupyingSchedule()->exists();
                $closureExists = CourtClosure::query()->where('court_id', $courtId)->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $otherHold = BookingHold::query()->where('court_id', $courtId)->where('token_hash', '<>', $tokenHash)->where('expires_at', '>', now())->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                if ($bookingExists || $closureExists || $otherHold) {
                    throw ValidationException::withMessages(['slots' => 'Khung giờ vừa được người khác giữ hoặc đặt. Vui lòng tải lại lịch.']);
                }
                BookingHold::query()->create(['token_hash' => $tokenHash, 'payment_reference' => $paymentReference, 'court_id' => $courtId, 'starts_at' => $start, 'ends_at' => $end, 'expires_at' => $expires]);
            }

            return ['expires_at' => $expires, 'payment_reference' => $paymentReference];
        });
    }

    public function releaseGuestHolds(string $tokenHash): void
    {
        BookingHold::query()->where('token_hash', $tokenHash)->delete();
    }

    public function guestPaymentInstructions(array $items, string $holdTokenHash): array
    {
        $holds = BookingHold::query()->where('token_hash', $holdTokenHash)
            ->where('expires_at', '>', now('UTC'))->get();

        return $this->assertGuestHoldsMatch($items, $holds);
    }

    public function createPendingBatchForGuest(array $items, array $orderAttributes, string $paymentReference, string $holdTokenHash, string $proofImagePath): BookingOrder
    {
        return DB::transaction(function () use ($items, $orderAttributes, $paymentReference, $holdTokenHash, $proofImagePath): BookingOrder {
            $courtIds = collect($items)->pluck('court.id')->unique()->sort()->values();
            $lockedCourts = [];
            foreach ($courtIds as $courtId) {
                $court = Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
                if (! $court->is_active) {
                    throw ValidationException::withMessages(['slots' => 'Một trong các sân đang tạm ngưng nhận đặt lịch.']);
                }
                $lockedCourts[$court->id] = $court;
            }

            $existingOrder = BookingOrder::query()->where('hold_token_hash', $holdTokenHash)->lockForUpdate()->first();
            if ($existingOrder) {
                $existingOrder->load(['bookings.court', 'bookings.payments.receipt']);
                $hasSamePayment = $existingOrder->bookings->flatMap(fn (Booking $booking) => $booking->payments)
                    ->contains(fn (Payment $payment): bool => $payment->qr_payload === $paymentReference);
                $hasSameStatusToken = hash_equals((string) $existingOrder->status_token_hash, (string) $orderAttributes['status_token_hash']);

                $submittedSlotKeys = [];
                foreach ($items as $item) {
                    for ($cursor = $item['starts_at_utc']; $cursor->lt($item['ends_at_utc']); $cursor = $cursor->addMinutes(30)) {
                        $submittedSlotKeys[] = $item['court']->id.'|'.$cursor->format('Y-m-d H:i:s');
                    }
                }
                $existingSlotKeys = [];
                foreach ($existingOrder->bookings as $booking) {
                    for ($cursor = CarbonImmutable::instance($booking->starts_at); $cursor->lt($booking->ends_at); $cursor = $cursor->addMinutes(30)) {
                        $existingSlotKeys[] = $booking->court_id.'|'.$cursor->format('Y-m-d H:i:s');
                    }
                }
                sort($submittedSlotKeys);
                sort($existingSlotKeys);

                $hasSameBookingDetails = $existingOrder->customer_name === $orderAttributes['customer_name']
                    && $existingOrder->customer_email === $orderAttributes['customer_email']
                    && $existingOrder->customer_phone === $orderAttributes['customer_phone']
                    && ($existingOrder->customer_note ?? '') === ($orderAttributes['customer_note'] ?? '')
                    && (string) $existingOrder->total_amount === (string) $orderAttributes['total_amount']
                    && $existingOrder->currency === $orderAttributes['currency']
                    && $existingSlotKeys === $submittedSlotKeys;

                if (! $hasSamePayment || ! $hasSameStatusToken || ! $hasSameBookingDetails) {
                    throw ValidationException::withMessages(['hold_token' => 'Lượt giữ sân này đã được dùng để tạo một đơn khác.']);
                }

                return $existingOrder;
            }

            $createdAt = CarbonImmutable::now('UTC');
            $holds = BookingHold::query()->where('token_hash', $holdTokenHash)->where('expires_at', '>', $createdAt)->lockForUpdate()->get();
            $holdDetails = $this->assertGuestHoldsMatch($items, $holds);
            if (! hash_equals($holdDetails['payment_reference'], $paymentReference)) {
                throw ValidationException::withMessages(['payment_reference' => 'Mã tham chiếu thanh toán không khớp với lượt giữ sân.']);
            }
            $paymentDeadlineAt = $holdDetails['expires_at'];
            $order = BookingOrder::query()->create([
                ...$orderAttributes,
                'status' => 'PENDING_PAYMENT',
                'payment_deadline_at' => $paymentDeadlineAt,
            ]);
            $created = [];
            foreach ($items as $index => $item) {
                $court = $lockedCourts[$item['court']->id];
                $startsAtUtc = $item['starts_at_utc'];
                $endsAtUtc = $item['ends_at_utc'];
                $field = "slots.{$index}.starts_at";
                $hours = CourtOperatingHour::query()
                    ->where('court_id', $court->id)
                    ->where('day_of_week', $item['day_of_week'])
                    ->where('is_closed', false)
                    ->first();

                if (! $hours || $item['local_starts_at'] < substr($hours->opens_at, 0, 5) || $item['local_ends_at'] > substr($hours->closes_at, 0, 5)) {
                    throw ValidationException::withMessages([$field => 'Khung giờ phải nằm trong giờ hoạt động của sân.']);
                }

                $hasClosure = CourtClosure::query()
                    ->where('court_id', $court->id)
                    ->where('starts_at', '<', $endsAtUtc)
                    ->where('ends_at', '>', $startsAtUtc)
                    ->exists();
                $hasBooking = Booking::query()
                    ->where('court_id', $court->id)
                    ->where('starts_at', '<', $endsAtUtc)
                    ->where('ends_at', '>', $startsAtUtc)
                    ->occupyingSchedule()
                    ->exists();

                if ($hasClosure || $hasBooking) {
                    throw ValidationException::withMessages([
                        $field => $hasClosure ? 'Khung giờ này đã bị khóa.' : 'Khung giờ này vừa được người khác đặt. Vui lòng tải lại lịch.',
                    ]);
                }

                $booking = Booking::query()->create([
                    ...$item['attributes'],
                    'booking_order_id' => $order->id,
                    'booking_code' => 'BI-'.$createdAt->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(5))),
                    'court_id' => $court->id,
                    'starts_at' => $startsAtUtc,
                    'ends_at' => $endsAtUtc,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'customer_note' => $order->customer_note,
                ]);
                $booking->statusHistory()->create([
                    'old_status' => null,
                    'new_status' => 'PENDING_PAYMENT',
                    'changed_by' => null,
                    'change_source' => 'CUSTOMER',
                    'note' => 'Khách tạo đơn đặt sân '.$order->booking_code.' và chờ thanh toán.',
                ]);
                $created[] = $booking->load(['court', 'payments']);
            }

            $primaryBooking = $created[0];
            BookingHold::query()->where('token_hash', $holdTokenHash)->delete();
            $payment = Payment::query()->create([
                'booking_id' => $primaryBooking->id,
                'provider' => 'MANUAL_QR',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'payment_method' => 'BANK_TRANSFER',
                'qr_payload' => $paymentReference,
                'proof_image_path' => $proofImagePath,
                'provider_metadata' => ['payment_reference' => $paymentReference, 'booking_order_code' => $order->booking_code],
            ]);
            $payment->receipt()->create(['status' => 'QUEUED']);

            $admins = DB::table('users')->where('is_active', true)
                ->whereIn(DB::raw('UPPER(role)'), ['ADMIN', 'STAFF'])
                ->get(['id', 'email']);
            foreach ($admins as $admin) {
                DB::table('notifications')->insertOrIgnore([
                    'booking_id' => $primaryBooking->id,
                    'user_id' => $admin->id,
                    'recipient' => $admin->email,
                    'channel' => 'IN_APP',
                    'type' => 'BOOKING_PENDING_PAYMENT',
                    'subject' => 'Đơn đặt sân mới',
                    'content' => "Đơn {$order->booking_code} của {$order->customer_name} đang chờ thanh toán.",
                    'status' => 'SENT',
                    'idempotency_key' => "booking-pending-order-{$order->id}-user-{$admin->id}",
                    'attempts' => 0,
                    'scheduled_at' => $createdAt,
                    'sent_at' => $createdAt,
                    'read_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            return $order->load(['bookings.court', 'bookings.payments.receipt']);
        });
    }

    public function findGuestStatusByTokenHash(string $tokenHash): ?BookingOrder
    {
        return BookingOrder::query()->with(['bookings.court', 'bookings.payments'])->where('status_token_hash', $tokenHash)->first();
    }

    public function createConfirmedForAdmin(
        Court $court,
        CarbonImmutable $startsAtUtc,
        CarbonImmutable $endsAtUtc,
        int $dayOfWeek,
        string $localStartsAt,
        string $localEndsAt,
        array $attributes,
        int $userId,
    ): Booking {
        return DB::transaction(function () use ($court, $startsAtUtc, $endsAtUtc, $dayOfWeek, $localStartsAt, $localEndsAt, $attributes, $userId): Booking {
            $lockedCourt = Court::query()->whereKey($court->id)->lockForUpdate()->firstOrFail();
            if (! $lockedCourt->is_active) {
                throw ValidationException::withMessages(['court_id' => 'Sân đang tạm ngưng nhận đặt lịch.']);
            }

            $hours = CourtOperatingHour::query()
                ->where('court_id', $court->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_closed', false)
                ->first();
            if (! $hours || $localStartsAt < substr($hours->opens_at, 0, 5) || $localEndsAt > substr($hours->closes_at, 0, 5)) {
                throw ValidationException::withMessages(['starts_at' => 'Thời gian đặt phải nằm trong giờ hoạt động của sân.']);
            }

            $hasClosure = CourtClosure::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->exists();
            $hasBooking = Booking::query()
                ->where('court_id', $court->id)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->occupyingSchedule()
                ->exists();

            if ($hasClosure || $hasBooking) {
                throw ValidationException::withMessages([
                    'starts_at' => $hasClosure ? 'Khoảng thời gian này đã bị khóa.' : 'Khoảng thời gian này đã có người đặt.',
                ]);
            }

            $order = BookingOrder::query()->create([
                'booking_code' => $attributes['booking_code'],
                'status' => 'PAID',
                'paid_at' => now(),
                'status_token_hash' => $attributes['status_token_hash'],
                'customer_name' => $attributes['customer_name'],
                'customer_email' => $attributes['customer_email'],
                'customer_phone' => $attributes['customer_phone'] ?? null,
                'base_amount' => $attributes['base_amount'],
                'discount_amount' => $attributes['discount_amount'],
                'total_amount' => $attributes['total_amount'],
                'currency' => $attributes['currency'],
                'customer_note' => $attributes['customer_note'] ?? null,
            ]);
            $booking = Booking::query()->create([
                ...$attributes,
                'booking_order_id' => $order->id,
                'court_id' => $court->id,
                'starts_at' => $startsAtUtc,
                'ends_at' => $endsAtUtc,
            ]);
            $booking->statusHistory()->create([
                'old_status' => null,
                'new_status' => 'PAID',
                'changed_by' => $userId,
                'change_source' => 'ADMIN',
                'note' => 'Admin đặt sân thay khách hàng.',
            ]);

            return $booking;
        });
    }

    private function assertGuestHoldsMatch(array $items, $holds): array
    {
        $now = CarbonImmutable::now('UTC');
        if ($holds->isEmpty()) {
            throw ValidationException::withMessages(['slots' => 'Thời gian giữ sân đã hết. Vui lòng chọn lại.']);
        }

        $paymentReferences = $holds->pluck('payment_reference')->filter()->unique()->values();
        if ($paymentReferences->count() !== 1) {
            throw ValidationException::withMessages(['payment_reference' => 'Mã tham chiếu giữ sân không hợp lệ. Vui lòng chọn lại.']);
        }

        $expiresAt = CarbonImmutable::instance($holds->first()->expires_at);
        if ($expiresAt->lte($now)) {
            throw ValidationException::withMessages(['slots' => 'Thời gian giữ sân đã hết. Vui lòng chọn lại.']);
        }

        $requiredSlots = collect($items)->sum(fn (array $item): int => (int) $item['starts_at_utc']->diffInMinutes($item['ends_at_utc']) / 30);
        $requiredKeys = [];
        foreach ($items as $item) {
            for ($cursor = $item['starts_at_utc']; $cursor->lt($item['ends_at_utc']); $cursor = $cursor->addMinutes(30)) {
                $requiredKeys[] = $item['court']->id.'|'.$cursor->format('Y-m-d H:i:s');
            }
        }
        $heldKeys = $holds->map(fn (BookingHold $hold): string => $hold->court_id.'|'.$hold->starts_at->utc()->format('Y-m-d H:i:s'))->all();
        sort($requiredKeys);
        sort($heldKeys);
        if ($holds->count() !== $requiredSlots || $heldKeys !== $requiredKeys) {
            throw ValidationException::withMessages(['slots' => 'Các khung giờ giữ tạm đã hết hạn hoặc không khớp. Vui lòng chọn lại.']);
        }

        return ['payment_reference' => $paymentReferences->first(), 'expires_at' => $expiresAt];
    }
}
