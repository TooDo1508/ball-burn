<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id', 'status', 'attempts', 'ocr_raw_text', 'extracted_data', 'mapping_result',
    'ocr_error', 'processing_started_at', 'processed_at',
])]
class PaymentReceipt extends Model
{
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'extracted_data' => 'array',
            'mapping_result' => 'array',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
