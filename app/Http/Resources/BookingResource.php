<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BookingResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->booking_code,
            'status' => $this->order?->status,
            'court' => $this->whenLoaded('court', fn () => [
                'name' => $this->court->name,
                'slug' => $this->court->slug,
            ]),
            'schedule' => [
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
                'timezone' => config('venue.timezone'),
            ],
            'amount' => [
                'base' => $this->base_amount,
                'discount' => $this->discount_amount,
                'total' => $this->total_amount,
                'currency' => $this->currency,
            ],
            'payment_deadline_at' => $this->order?->payment_deadline_at,
        ];
    }
}
