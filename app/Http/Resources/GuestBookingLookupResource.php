<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;

class GuestBookingLookupResource extends ApiResponse
{
    public function toArray(Request $request): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $status = $this->status === 'PENDING_PAYMENT' && $this->payment_deadline_at?->lte(now('UTC'))
            ? 'EXPIRED'
            : $this->status;
        $cancelledAt = $this->bookings->flatMap->statusHistory
            ->first(fn ($history): bool => $history->new_status === 'CANCELLED')?->created_at;

        return [
            'booking_code' => $this->booking_code,
            'status' => $status,
            'created_at' => $this->created_at?->timezone($timezone)->toISOString(),
            'payment_deadline_at' => $this->payment_deadline_at?->timezone($timezone)->toISOString(),
            'paid_at' => $this->paid_at?->timezone($timezone)->toISOString(),
            'cancelled_at' => $cancelledAt?->timezone($timezone)->toISOString(),
            'needs_review' => $this->paymentWebhookEvents->contains('outcome', 'NEEDS_REVIEW'),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'customer' => [
                'name' => $this->maskName((string) $this->customer_name),
                'phone' => $this->maskPhone((string) $this->customer_phone),
                'email' => $this->maskEmail($this->customer_email),
            ],
            'bookings' => $this->bookings->map(fn (Booking $booking): array => [
                'court' => $booking->court->name,
                'image_url' => $this->courtImageUrl($booking),
                'date' => $booking->starts_at->timezone($timezone)->format('Y-m-d'),
                'starts_at' => $booking->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
            ])->values()->all(),
        ];
    }

    private function maskName(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)) ?: [])->map(function (string $part): string {
            $length = mb_strlen($part);

            return $length <= 1 ? $part : mb_substr($part, 0, 1).str_repeat('*', max(1, $length - 1));
        })->implode(' ');
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    private function courtImageUrl(Booking $booking): ?string
    {
        $path = $booking->court->images->firstWhere('is_primary', true)?->image_path
            ?? $booking->court->images->first()?->image_path;
        if (! $path) {
            return null;
        }

        return preg_match('~^https?://~i', $path) || str_starts_with($path, '/') ? $path : '/storage/'.$path;
    }
}
