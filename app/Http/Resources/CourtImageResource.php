<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourtImageResource extends ApiResponse
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'url' => Storage::disk('public')->url($this->image_path),
            'alt' => $this->alt_text,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}
