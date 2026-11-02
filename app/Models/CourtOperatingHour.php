<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['court_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed'])]
class CourtOperatingHour extends Model
{
    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'is_closed' => 'boolean'];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
