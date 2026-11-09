<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResponse extends JsonResource
{
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => null,
            'meta' => null,
            'errors' => null,
        ];
    }
}
