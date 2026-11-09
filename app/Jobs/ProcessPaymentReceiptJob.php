<?php

namespace App\Jobs;

use App\Services\Payments\PaymentReceiptProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessPaymentReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 50;

    public function __construct(public int $receiptId) {}

    public function handle(PaymentReceiptProcessingService $processor): void
    {
        $processor->process($this->receiptId);
    }

    public function failed(?Throwable $exception): void
    {
        app(PaymentReceiptProcessingService::class)->markFailed($this->receiptId);
    }
}
