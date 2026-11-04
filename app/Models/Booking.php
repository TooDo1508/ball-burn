<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_code', 'status_token_hash', 'court_id', 'customer_name', 'customer_email',
    'customer_phone', 'starts_at', 'ends_at', 'status', 'payment_deadline_at',
    'base_amount', 'discount_amount', 'total_amount', 'currency', 'customer_note',
    'cancellation_reason', 'confirmed_at', 'cancelled_at',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'court_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_deadline_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'base_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('created_at');
    }
}
