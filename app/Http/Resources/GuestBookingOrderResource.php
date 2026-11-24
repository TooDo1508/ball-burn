<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;

class GuestBookingOrderResource extends ApiResponse
{
    public function toArray(Request $request): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $displayStatus = $this->status === 'PENDING_PAYMENT' && $this->payment_deadline_at?->lte(now('UTC'))
            ? 'EXPIRED'
            : $this->status;

        return [
            'booking_code' => $this->booking_code,
            'bookings' => $this->bookings->map(fn (Booking $segment): array => [
                'court' => $segment->court->name,
                'date' => $segment->starts_at->timezone($timezone)->format('Y-m-d'),
                'starts_at' => $segment->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $segment->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $segment->total_amount,
                'currency' => $segment->currency,
                'status' => $displayStatus,
            ])->values()->all(),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'status' => $displayStatus,
            'payment_deadline_at' => $this->payment_deadline_at?->toISOString(),
            'payment_submitted' => $this->bookings->flatMap(fn (Booking $segment) => $segment->payments)
                ->contains(fn ($payment): bool => filled($payment->proof_image_path)),
        ];
    }
}
