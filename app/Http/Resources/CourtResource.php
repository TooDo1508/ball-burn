<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CourtResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'surface_type' => $this->surface_type,
            'capacity' => $this->capacity,
            'price' => [
                'amount' => $this->price_per_hour,
                'currency' => $this->currency,
                'unit' => 'hour',
            ],
            'images' => CourtImageResource::collection($this->whenLoaded('images')),
            'operating_hours' => OperatingHoursResource::collection($this->whenLoaded('operatingHours')),
        ];
    }
}
