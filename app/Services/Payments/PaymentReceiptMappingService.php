<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentMappingRepository;
use App\Services\Settings\ContactSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class PaymentReceiptMappingService
{
    public function __construct(
        private readonly PaymentMappingRepository $payments,
        private readonly ContactSettingsService $settings,
    ) {}

    /**
     * Map normalized OCR fields to the site's existing manual bank-transfer payment.
     * A MATCHED result is a receipt-content match only and must never confirm payment.
     *
     * @param  array{payment_reference?: mixed, payment_code?: mixed, amount?: mixed, currency?: mixed, transaction_id?: mixed, recipient_account?: mixed}  $extracted
     * @return array<string, mixed>
     */
    public function map(array $extracted): array
    {
        $reference = $this->normalizeReference($extracted['payment_reference'] ?? $extracted['payment_code'] ?? null);
        if ($reference === null) {
            return $this->unmatched('payment_reference_missing', [
                'payment_reference' => ['status' => 'MISSING'],
            ]);
        }

        $candidates = $this->payments->findByPaymentReference($reference);
        if ($candidates->count() === 0) {
            return $this->unmatched('payment_reference_unknown', [
                'payment_reference' => ['status' => 'NOT_FOUND', 'observed' => $reference],
            ]);
        }
        if ($candidates->count() > 1) {
            return $this->unmatched('payment_reference_ambiguous', [
                'payment_reference' => ['status' => 'AMBIGUOUS', 'observed' => $reference],
            ]);
        }

        /** @var Payment $payment */
        $payment = $candidates->first();
        $order = $payment->booking?->order;
        $observedAmount = $this->normalizeAmount($extracted['amount'] ?? null);
        $expectedAmount = $this->normalizeAmount($payment->amount);
        $observedCurrency = $this->normalizeCurrency($extracted['currency'] ?? null);
        $expectedCurrency = $this->normalizeCurrency($payment->currency);
        $transactionId = $this->normalizeTransactionId($extracted['transaction_id'] ?? null);
        $recipientAccountRaw = $extracted['recipient_account'] ?? null;
        $recipientAccount = $this->normalizeRecipientAccount($recipientAccountRaw);
        $expectedRecipientAccount = $this->settings->paymentAccountNumber();

        $checks = [
            'payment_reference' => ['status' => 'MATCHED', 'observed' => $reference],
            'amount' => [
                'status' => $observedAmount === null ? 'MISSING' : ($observedAmount === $expectedAmount ? 'MATCHED' : 'MISMATCH'),
                'observed' => $observedAmount,
                'expected' => $expectedAmount,
            ],
            'currency' => [
                'status' => $observedCurrency === null ? 'MISSING' : ($observedCurrency === $expectedCurrency ? 'MATCHED' : 'MISMATCH'),
                'observed' => $observedCurrency,
                'expected' => $expectedCurrency,
            ],
            'payment_state' => $this->paymentStateCheck($payment, $order?->status, $order?->payment_deadline_at),
            'recipient_account' => $this->recipientAccountCheck($recipientAccountRaw, $recipientAccount, $expectedRecipientAccount),
        ];
        $reasons = [];

        if ($observedAmount === null) {
            $reasons[] = 'amount_missing_or_invalid';
        } elseif ($observedAmount !== $expectedAmount) {
            $reasons[] = 'amount_mismatch';
        }
        if ($observedCurrency === null) {
            $reasons[] = 'currency_missing_or_invalid';
        } elseif ($observedCurrency !== $expectedCurrency) {
            $reasons[] = 'currency_mismatch';
        }
        if ($checks['payment_state']['status'] !== 'ELIGIBLE') {
            $reasons[] = $checks['payment_state']['reason'];
        }
        if ($checks['recipient_account']['status'] === 'MISMATCH') {
            $reasons[] = 'recipient_account_mismatch';
        } elseif (in_array($checks['recipient_account']['status'], ['INVALID', 'NOT_CONFIGURED'], true)) {
            $reasons[] = 'recipient_account_unverifiable';
        }

        if ($transactionId !== null) {
            $isDuplicate = $this->payments->hasBankTransactionId($transactionId);
            $checks['transaction_id'] = [
                'status' => $isDuplicate ? 'DUPLICATE' : 'UNIQUE',
                'observed' => $transactionId,
            ];
            if ($isDuplicate) {
                $reasons[] = 'transaction_id_already_recorded';
            }
        } else {
            $checks['transaction_id'] = ['status' => 'NOT_PROVIDED'];
        }

        return [
            'status' => $reasons === [] ? 'MATCHED' : 'NEEDS_REVIEW',
            'payment_id' => $payment->id,
            'booking_order_code' => $order?->booking_code,
            'checks' => $checks,
            'reasons' => $reasons,
            'payment_can_be_marked_paid' => false,
        ];
    }

    private function unmatched(string $reason, array $checks): array
    {
        return [
            'status' => 'NEEDS_REVIEW',
            'payment_id' => null,
            'booking_order_code' => null,
            'checks' => $checks,
            'reasons' => [$reason],
            'payment_can_be_marked_paid' => false,
        ];
    }

    private function normalizeReference(mixed $reference): ?string
    {
        if (! is_string($reference) || trim($reference) === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', Str::upper(trim($reference))) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeAmount(mixed $amount): ?string
    {
        if (! is_string($amount) && ! is_int($amount)) {
            return null;
        }

        $amount = trim((string) $amount);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');
    }

    private function normalizeCurrency(mixed $currency): ?string
    {
        if (! is_string($currency) || trim($currency) === '') {
            return null;
        }

        return Str::upper(trim($currency));
    }

    private function normalizeTransactionId(mixed $transactionId): ?string
    {
        if (! is_string($transactionId) || trim($transactionId) === '') {
            return null;
        }

        return trim($transactionId);
    }

    private function normalizeRecipientAccount(mixed $accountNumber): ?string
    {
        if (! is_string($accountNumber) || trim($accountNumber) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $accountNumber) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function recipientAccountCheck(mixed $raw, ?string $observed, ?string $expected): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return ['status' => 'NOT_READ'];
        }
        if ($observed === null) {
            return ['status' => 'INVALID'];
        }
        if (! $expected) {
            return ['status' => 'NOT_CONFIGURED', 'observed' => $this->maskAccountNumber($observed)];
        }

        return [
            'status' => $observed === $expected ? 'MATCHED' : 'MISMATCH',
            'observed' => $this->maskAccountNumber($observed),
            'expected' => $this->maskAccountNumber($expected),
        ];
    }

    private function maskAccountNumber(string $accountNumber): string
    {
        return str_repeat('*', max(strlen($accountNumber) - 4, 0)).substr($accountNumber, -4);
    }

    private function paymentStateCheck(Payment $payment, ?string $orderStatus, mixed $deadline): array
    {
        if ($payment->status !== 'PENDING') {
            return ['status' => 'NEEDS_REVIEW', 'reason' => 'payment_not_pending'];
        }
        if ($orderStatus !== 'PENDING_PAYMENT') {
            return ['status' => 'NEEDS_REVIEW', 'reason' => 'booking_not_pending'];
        }
        if (! $deadline || CarbonImmutable::parse($deadline)->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            return ['status' => 'NEEDS_REVIEW', 'reason' => 'payment_window_expired'];
        }

        return ['status' => 'ELIGIBLE'];
    }
}
