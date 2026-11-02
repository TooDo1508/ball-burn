<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResponse extends JsonResource
{
    public static function envelope(): array
    {
        return [
            'success' => true,
            'message' => null,
            'data' => null,
            'meta' => null,
            'errors' => null,
        ];
    }

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
