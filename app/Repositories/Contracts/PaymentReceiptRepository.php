<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentReceipt;

interface PaymentReceiptRepository
{
    public function claimForProcessing(int $id): ?PaymentReceipt;

    /**
     * @param array<string, mixed> $extractedData
     * @param array<string, mixed> $mappingResult
     */
    public function saveResult(
        int $id,
        int $attempt,
        string $status,
        string $rawText,
        array $extractedData,
        array $mappingResult,
        ?string $error = null,
    ): void;

    public function queueRetry(int $id, int $attempt): void;

    public function markFailed(int $id): void;
}
