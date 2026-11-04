<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'old_status', 'new_status', 'changed_by', 'change_source', 'note'])]
class BookingStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'booking_status_history';

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
