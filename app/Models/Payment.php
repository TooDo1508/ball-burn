<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'booking_id', 'provider', 'provider_transaction_id', 'provider_order_id', 'amount', 'currency',
    'payment_method', 'qr_payload', 'checkout_url', 'provider_metadata', 'proof_image_path', 'paid_at',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }
}
