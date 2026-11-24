<?php

namespace App\Console\Commands;

use App\Models\PaymentWebhookEvent;
use App\Services\Payments\SePayWebhookService;
use Illuminate\Console\Command;

class ReprocessSePayWebhooks extends Command
{
    protected $signature = 'payments:sepay-reprocess {--limit=50}';

    protected $description = 'Retry recoverable SePay webhook inbox events';

    public function handle(SePayWebhookService $service): int
    {
        if (! config('sepay.reprocess_enabled')) {
            $this->components->info('SePay reprocessing is disabled.');

            return self::SUCCESS;
        }

        $limit = max(1, min(200, (int) $this->option('limit')));
        $stale = now('UTC')->subMinutes(5);
        $ids = PaymentWebhookEvent::query()
            ->whereIn('processing_status', ['RECEIVED', 'FAILED'])
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now('UTC')))
            ->orWhere(fn ($query) => $query->where('processing_status', 'PROCESSING')->where('processing_started_at', '<=', $stale))
            ->orderBy('id')->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            try {
                $service->process((int) $id);
            } catch (\Throwable $exception) {
                PaymentWebhookEvent::query()->whereKey($id)->update([
                    'processing_status' => 'FAILED',
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                    'next_attempt_at' => now('UTC')->addMinutes(5),
                ]);
                $this->components->error("Event {$id}: {$exception->getMessage()}");
            }
        }

        $this->components->info("Processed {$ids->count()} SePay event(s).");

        return self::SUCCESS;
    }
}
