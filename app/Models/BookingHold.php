<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingHold extends Model
{
    protected $fillable = ['token_hash', 'payment_reference', 'court_id', 'starts_at', 'ends_at', 'expires_at'];

    protected function casts(): array
    {
        return ['court_id' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
