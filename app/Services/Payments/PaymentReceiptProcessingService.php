<?php

namespace App\Services\Payments;

use App\Repositories\Contracts\PaymentReceiptRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentReceiptProcessingService
{
    public function __construct(
        private readonly PaymentReceiptRepository $receipts,
        private readonly ReceiptOcrInterface $ocr,
        private readonly PaymentReceiptOcrParser $parser,
        private readonly PaymentReceiptMappingService $mapping,
    ) {}

    public function process(int $receiptId): void
    {
        $receipt = $this->receipts->claimForProcessing($receiptId);
        if (! $receipt) {
            return;
        }

        $attempt = $receipt->attempts;

        try {
            $proofPath = $receipt->payment?->proof_image_path;
            if (! $proofPath || ! Storage::disk('local')->exists($proofPath)) {
                $mapping = $this->mapping->map([]);
                $mapping['reasons'] = ['proof_image_missing'];
                $this->receipts->saveResult(
                    $receipt->id,
                    $attempt,
                    'NEEDS_REVIEW',
                    '',
                    [],
                    $mapping,
                    'The uploaded receipt image is not available.',
                );

                return;
            }

            $rawText = $this->ocr->extract(Storage::disk('local')->path($proofPath));
            $extracted = $this->parser->parse($rawText);
            $mapping = $this->mapping->map($extracted);

            $this->receipts->saveResult(
                $receipt->id,
                $attempt,
                $mapping['status'],
                $rawText,
                $extracted,
                $mapping,
            );
        } catch (Throwable $exception) {
            $this->receipts->queueRetry($receipt->id, $attempt);
            Log::warning('Payment receipt OCR failed; the queued job will retry.', [
                'payment_receipt_id' => $receipt->id,
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }

    public function markFailed(int $receiptId): void
    {
        $this->receipts->markFailed($receiptId);
    }
}
