<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'provider', 'provider_event_id', 'event_type', 'payload', 'payload_hash', 'processing_status', 'attempts',
    'next_attempt_at', 'processing_started_at', 'received_at', 'processed_at', 'outcome', 'booking_order_id',
    'payment_id', 'bank_transfer_transaction_id', 'last_error',
])]
class PaymentWebhookEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'processing_started_at' => 'datetime',
        ];
    }
}
