<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'court_id', 'day_type', 'starts_at', 'ends_at', 'price_per_hour', 'currency',
    'effective_from', 'effective_until', 'is_active',
])]
class CourtPriceRule extends Model
{
    protected function casts(): array
    {
        return [
            'court_id' => 'integer',
            'price_per_hour' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
