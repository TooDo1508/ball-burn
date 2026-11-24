<?php

namespace App\Services\Payments;

use App\Models\BankTransferTransaction;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SePayWebhookService
{
    public function receive(array $payload): void
    {
        $provider = 'SEPAY:'.strtoupper((string) config('sepay.environment', 'live'));
        $eventId = (string) $payload['id'];
        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $payloadHash = hash('sha256', $canonical);

        try {
            $event = PaymentWebhookEvent::query()->create([
                'provider' => $provider,
                'provider_event_id' => $eventId,
                'event_type' => 'BANK_TRANSACTION',
                'payload' => $payload,
                'payload_hash' => $payloadHash,
                'processing_status' => 'RECEIVED',
                'received_at' => now('UTC'),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }
            $event = PaymentWebhookEvent::query()->where('provider', $provider)->where('provider_event_id', $eventId)->firstOrFail();
            if (! hash_equals((string) $event->payload_hash, $payloadHash)) {
                throw new RuntimeException('SePay event ID was reused with a different payload.');
            }
            if (in_array($event->processing_status, ['PROCESSED', 'IGNORED'], true)) {
                return;
            }
        }

        try {
            $this->process($event->id);
        } catch (Throwable $exception) {
            PaymentWebhookEvent::query()->whereKey($event->id)->update([
                'processing_status' => 'FAILED',
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                'next_attempt_at' => now('UTC')->addMinute(),
            ]);
            throw $exception;
        }
    }

    public function process(int $eventId): void
    {
        DB::transaction(function () use ($eventId): void {
            $event = PaymentWebhookEvent::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            if (in_array($event->processing_status, ['PROCESSED', 'IGNORED'], true)) {
                return;
            }
            $event->update([
                'processing_status' => 'PROCESSING',
                'processing_started_at' => now('UTC'),
                'attempts' => $event->attempts + 1,
                'last_error' => null,
            ]);
            $payload = $event->payload;

            if (($payload['transferType'] ?? null) !== 'in') {
                $this->finish($event, 'IGNORED', 'UNMATCHED');

                return;
            }

            $references = $this->references($payload);
            if (count($references) !== 1) {
                $transaction = $this->recordTransaction($event, $payload, null, null, 'UNMATCHED');
                $this->finish($event, 'PROCESSED', 'UNMATCHED', null, null, $transaction);

                return;
            }

            $order = BookingOrder::query()->where('payment_reference', $references[0])->lockForUpdate()->first();
            $payment = $order ? Payment::query()
                ->whereIn('booking_id', Booking::query()->select('id')->where('booking_order_id', $order->id))
                ->where('provider', 'SEPAY')->lockForUpdate()->first() : null;
            $transaction = $this->recordTransaction($event, $payload, $order, $payment, 'NEEDS_REVIEW');

            if (! $order || ! $payment) {
                $this->finish($event, 'PROCESSED', 'UNMATCHED', null, null, $transaction);

                return;
            }
            if ($order->status === 'PAID') {
                $this->finish($event, 'PROCESSED', 'ALREADY_PAID', $order, $payment, $transaction);

                return;
            }

            $metadata = $payment->provider_metadata ?? [];
            $receiverMatches = strcasecmp((string) $payload['gateway'], (string) ($metadata['bank_code'] ?? '')) === 0
                && hash_equals((string) ($metadata['account_number'] ?? ''), (string) $payload['accountNumber']);
            $normalizedAmount = $this->normalizeAmount((string) $payload['transferAmount']);
            $amountMatches = $normalizedAmount === (string) $payment->amount;
            $now = CarbonImmutable::now('UTC');
            $eligible = $order->status === 'PENDING_PAYMENT' && $order->payment_deadline_at?->gt($now);

            if (! $receiverMatches || ! $amountMatches || ! $eligible || ! config('sepay.auto_confirm_enabled')) {
                $transaction->update(['status' => 'NEEDS_REVIEW']);
                $this->finish($event, 'PROCESSED', 'NEEDS_REVIEW', $order, $payment, $transaction);

                return;
            }

            $segments = Booking::query()->where('booking_order_id', $order->id)->orderBy('court_id')->lockForUpdate()->get();
            foreach ($segments->pluck('court_id')->unique()->sort() as $courtId) {
                Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
            }
            foreach ($segments as $segment) {
                $conflict = Booking::query()->where('court_id', $segment->court_id)->where('id', '<>', $segment->id)
                    ->where('starts_at', '<', $segment->ends_at)->where('ends_at', '>', $segment->starts_at)
                    ->occupyingSchedule()->exists();
                $held = BookingHold::query()->where('court_id', $segment->court_id)->where('expires_at', '>', $now)
                    ->where('starts_at', '<', $segment->ends_at)->where('ends_at', '>', $segment->starts_at)->exists();
                $closed = CourtClosure::query()->where('court_id', $segment->court_id)
                    ->where('starts_at', '<', $segment->ends_at)->where('ends_at', '>', $segment->starts_at)->exists();
                if ($conflict || $held || $closed) {
                    $transaction->update(['status' => 'NEEDS_REVIEW', 'review_note' => 'Schedule conflict at confirmation time.']);
                    $this->finish($event, 'PROCESSED', 'NEEDS_REVIEW', $order, $payment, $transaction);

                    return;
                }
            }

            $paidAt = $now;
            $order->update(['status' => 'PAID', 'paid_at' => $paidAt]);
            $payment->update(['provider_transaction_id' => (string) $payload['id'], 'paid_at' => $paidAt]);
            $transaction->update(['status' => 'MATCHED']);
            foreach ($segments as $segment) {
                $segment->statusHistory()->create([
                    'old_status' => 'PENDING_PAYMENT', 'new_status' => 'PAID', 'change_source' => 'PAYMENT_WEBHOOK',
                    'note' => 'SePay xác nhận giao dịch '.$payload['id'].'.',
                ]);
            }
            $this->finish($event, 'PROCESSED', 'APPLIED', $order, $payment, $transaction);
        }, 3);
    }

    private function references(array $payload): array
    {
        $prefix = preg_quote((string) config('sepay.payment_prefix', 'BBPAY'), '/');
        $pattern = '/(?<![A-Z0-9])'.$prefix.'[A-Z0-9]{10}(?![A-Z0-9])/i';
        $found = [];
        foreach ([(string) ($payload['code'] ?? ''), (string) ($payload['content'] ?? '')] as $value) {
            preg_match_all($pattern, strtoupper($value), $matches);
            $found = [...$found, ...$matches[0]];
        }

        return array_values(array_unique($found));
    }

    private function recordTransaction(PaymentWebhookEvent $event, array $payload, ?BookingOrder $order, ?Payment $payment, string $status): BankTransferTransaction
    {
        $providerTransactionId = (string) $payload['id'];
        $transaction = BankTransferTransaction::query()->firstOrCreate(
            ['bank_transaction_id' => $event->provider.':'.$providerTransactionId],
            [
                'provider' => $event->provider, 'provider_transaction_id' => $providerTransactionId,
                'bank_reference_code' => $payload['referenceCode'] ?? null, 'receiver_bank_code' => $payload['gateway'],
                'receiver_account_number' => $payload['accountNumber'], 'receiver_sub_account' => $payload['subAccount'] ?? null,
                'booking_id' => $order?->bookings()->value('id'), 'payment_id' => $payment?->id,
                'amount' => $this->normalizeAmount((string) $payload['transferAmount']), 'currency' => 'VND',
                'transaction_at' => CarbonImmutable::createFromFormat('Y-m-d H:i:s', $payload['transactionDate'], 'Asia/Ho_Chi_Minh')->utc(),
                'transfer_description' => $payload['content'] ?? null, 'status' => $status,
            ]
        );

        return $transaction;
    }

    private function finish(PaymentWebhookEvent $event, string $status, string $outcome, ?BookingOrder $order = null, ?Payment $payment = null, ?BankTransferTransaction $transaction = null): void
    {
        $event->update([
            'processing_status' => $status, 'outcome' => $outcome, 'processed_at' => now('UTC'),
            'next_attempt_at' => null, 'booking_order_id' => $order?->id, 'payment_id' => $payment?->id,
            'bank_transfer_transaction_id' => $transaction?->id,
        ]);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }

    private function normalizeAmount(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        $normalizedWhole = ltrim($whole, '0');

        return ($normalizedWhole === '' ? '0' : $normalizedWhole).'.'.substr(str_pad($fraction, 2, '0'), 0, 2);
    }
}
