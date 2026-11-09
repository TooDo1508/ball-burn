<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

class PaymentReceiptOcrParser
{
    /**
     * @return array{payment_reference: ?string, amount: ?string, currency: ?string, transaction_id: ?string, recipient_account: ?string}
     */
    public function parse(string $rawText): array
    {
        $text = Str::upper(Str::ascii($rawText));
        $compactText = preg_replace('/[^A-Z0-9]/', '', $text) ?? '';
        $reference = preg_match('/BBPAY[A-Z0-9]{10}/', $compactText, $matches) === 1
            ? $matches[0]
            : null;

        $lines = preg_split('/\R/u', $text) ?: [];
        $amountToken = $this->valueAfterLabel(
            $lines,
            '/\bSO\s+TIEN\b|\bAMOUNT\b/',
            '/(?<![A-Z0-9])(\d[\d., ]{0,22}\d|\d)(?=\s*(?:VND|DONG|D|USD|EUR|SGD|AUD|GBP|JPY|CNY|THB)?(?![A-Z0-9]))/',
        );

        return [
            'payment_reference' => $reference,
            'amount' => $amountToken === null ? null : $this->normalizeAmount($amountToken),
            'currency' => $this->extractCurrency($rawText, $text),
            'transaction_id' => $this->valueAfterLabel(
                $lines,
                '/\bMA GIAO DICH\b|\bSO GIAO DICH\b|\bTRANSACTION\s+(?:ID|NO|NUMBER)\b|\bTRACE\s+ID\b/',
                '/(?<![A-Z0-9])([A-Z0-9][A-Z0-9-]{5,39})(?![A-Z0-9])/',
            ),
            'recipient_account' => $this->valueAfterLabel(
                $lines,
                '/\bTAI KHOAN\s+(?:THU HUONG|NHAN)\b|\b(?:RECEIVING|BENEFICIARY)\s+ACCOUNT\b/',
                '/(?<!\d)(\d{6,19})(?!\d)/',
            ),
        ];
    }

    /** @param array<int, string> $lines */
    private function valueAfterLabel(array $lines, string $labelPattern, string $valuePattern): ?string
    {
        foreach ($lines as $index => $line) {
            if (preg_match($labelPattern, $line, $label, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $labelEnd = $label[0][1] + strlen($label[0][0]);
            $candidates = [substr($line, $labelEnd), $lines[$index + 1] ?? '', $lines[$index + 2] ?? ''];
            foreach ($candidates as $candidate) {
                if (preg_match($valuePattern, $candidate, $value) === 1) {
                    return trim($value[1]);
                }
            }
        }

        return null;
    }

    private function normalizeAmount(string $amount): ?string
    {
        $amount = preg_replace('/\s+/', '', trim($amount)) ?? '';
        $amount = preg_replace('/[^\d.,]/', '', $amount) ?? '';
        if ($amount === '' || ! preg_match('/\d/', $amount)) {
            return null;
        }

        $lastDot = strrpos($amount, '.');
        $lastComma = strrpos($amount, ',');
        $lastSeparator = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);

        if ($lastDot !== false && $lastComma !== false) {
            $fractionLength = strlen($amount) - $lastSeparator - 1;
            if ($fractionLength > 0 && $fractionLength <= 2) {
                $whole = preg_replace('/[.,]/', '', substr($amount, 0, $lastSeparator)) ?? '';
                $fraction = substr($amount, $lastSeparator + 1);

                return $this->decimalAmount($whole, $fraction);
            }

            return $this->decimalAmount(preg_replace('/[.,]/', '', $amount) ?? '', '');
        }

        if ($lastSeparator !== -1) {
            $fractionLength = strlen($amount) - $lastSeparator - 1;
            if (substr_count($amount, $amount[$lastSeparator]) === 1 && $fractionLength > 0 && $fractionLength <= 2) {
                $whole = preg_replace('/[.,]/', '', substr($amount, 0, $lastSeparator)) ?? '';

                return $this->decimalAmount($whole, substr($amount, $lastSeparator + 1));
            }
        }

        return $this->decimalAmount(preg_replace('/[.,]/', '', $amount) ?? '', '');
    }

    private function decimalAmount(string $whole, string $fraction): ?string
    {
        $whole = ltrim($whole, '0') ?: '0';
        if (! ctype_digit($whole) || strlen($whole) > 12 || strlen($fraction) > 2) {
            return null;
        }

        return $whole.'.'.str_pad($fraction, 2, '0');
    }

    private function extractCurrency(string $rawText, string $normalizedText): ?string
    {
        if (preg_match('/\b(VND|USD|EUR|SGD|AUD|GBP|JPY|CNY|THB)\b/', $normalizedText, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\bDONG\b|\bD\b/', $normalizedText) === 1 || preg_match('/[đĐ₫]/u', $rawText) === 1) {
            return 'VND';
        }

        return null;
    }
}
