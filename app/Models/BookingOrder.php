<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_code', 'status', 'status_token_hash', 'hold_token_hash', 'customer_name', 'customer_email', 'customer_phone',
    'payment_deadline_at', 'paid_at', 'base_amount', 'discount_amount', 'total_amount', 'currency', 'customer_note',
])]
class BookingOrder extends Model
{
    protected function casts(): array
    {
        return [
            'payment_deadline_at' => 'datetime',
            'paid_at' => 'datetime',
            'base_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderBy('starts_at');
    }
}
