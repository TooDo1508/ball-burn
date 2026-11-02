<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OperatingHoursResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'day_of_week' => $this->day_of_week,
            'opens_at' => $this->opens_at,
            'closes_at' => $this->closes_at,
            'is_closed' => (bool) $this->is_closed,
        ];
    }
}
