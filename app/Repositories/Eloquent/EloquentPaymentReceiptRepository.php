<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentReceipt;
use App\Repositories\Contracts\PaymentReceiptRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class EloquentPaymentReceiptRepository implements PaymentReceiptRepository
{
    public function claimForProcessing(int $id): ?PaymentReceipt
    {
        return DB::transaction(function () use ($id): ?PaymentReceipt {
            $receipt = PaymentReceipt::query()
                ->with(['payment.booking.order'])
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $receipt) {
                return null;
            }

            $staleProcessing = $receipt->status === 'PROCESSING'
                && $receipt->processing_started_at?->lessThanOrEqualTo(CarbonImmutable::now('UTC')->subSeconds(75));

            if (! in_array($receipt->status, ['QUEUED', 'FAILED'], true) && ! $staleProcessing) {
                return null;
            }

            $receipt->update([
                'status' => 'PROCESSING',
                'attempts' => $receipt->attempts + 1,
                'ocr_error' => null,
                'processing_started_at' => CarbonImmutable::now('UTC'),
            ]);

            return $receipt->fresh(['payment.booking.order']);
        });
    }

    public function saveResult(
        int $id,
        int $attempt,
        string $status,
        string $rawText,
        array $extractedData,
        array $mappingResult,
        ?string $error = null,
    ): void {
        PaymentReceipt::query()
            ->whereKey($id)
            ->where('status', 'PROCESSING')
            ->where('attempts', $attempt)
            ->update([
                'status' => $status,
                'ocr_raw_text' => mb_substr($rawText, 0, 20000),
                'extracted_data' => $extractedData,
                'mapping_result' => $mappingResult,
                'ocr_error' => $error,
                'processing_started_at' => null,
                'processed_at' => CarbonImmutable::now('UTC'),
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
    }

    public function queueRetry(int $id, int $attempt): void
    {
        PaymentReceipt::query()
            ->whereKey($id)
            ->where('status', 'PROCESSING')
            ->where('attempts', $attempt)
            ->update([
                'status' => 'QUEUED',
                'ocr_error' => 'OCR processing failed; the queue will retry.',
                'processing_started_at' => null,
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
    }

    public function markFailed(int $id): void
    {
        PaymentReceipt::query()
            ->whereKey($id)
            ->whereNotIn('status', ['MATCHED', 'NEEDS_REVIEW'])
            ->update([
                'status' => 'FAILED',
                'ocr_error' => 'OCR processing failed after the retry limit.',
                'processing_started_at' => null,
                'updated_at' => CarbonImmutable::now('UTC'),
            ]);
    }
}
