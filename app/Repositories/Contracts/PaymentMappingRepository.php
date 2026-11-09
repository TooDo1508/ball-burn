<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PaymentMappingRepository
{
    public function findByPaymentReference(string $paymentReference): Collection;

    public function hasBankTransactionId(string $transactionId): bool;
}
