<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AvailabilitySlotResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'starts_at' => $this['starts_at'],
            'ends_at' => $this['ends_at'],
            'status' => $this['status'],
            'price' => [
                'amount' => $this['price']['amount'],
                'currency' => $this['price']['currency'],
            ],
        ];
    }
}
