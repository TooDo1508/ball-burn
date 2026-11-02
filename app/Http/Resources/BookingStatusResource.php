<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BookingStatusResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->booking_code,
            'status' => $this->status,
            'court' => $this->whenLoaded('court', fn () => $this->court->only(['name', 'slug'])),
            'schedule' => [
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
                'timezone' => config('app.timezone'),
            ],
            'amount' => [
                'total' => $this->total_amount,
                'currency' => $this->currency,
            ],
            'payment_deadline_at' => $this->payment_deadline_at,
        ];
    }
}
