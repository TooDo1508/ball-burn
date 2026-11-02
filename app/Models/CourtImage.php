<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['court_id', 'image_path', 'alt_text', 'sort_order', 'is_primary'])]
class CourtImage extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'sort_order' => 'integer', 'created_at' => 'datetime'];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
