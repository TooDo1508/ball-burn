<?php

namespace App\Repositories\Eloquent;

use App\Models\BankTransferTransaction;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentMappingRepository;
use Illuminate\Support\Collection;

class EloquentPaymentMappingRepository implements PaymentMappingRepository
{
    public function findByPaymentReference(string $paymentReference): Collection
    {
        $references = [$paymentReference];
        if (preg_match('/^BBPAY([A-Z0-9]{10})$/D', $paymentReference, $matches)) {
            $references[] = 'BBPAY-'.$matches[1];
        }

        return Payment::query()
            ->with(['booking.order'])
            ->where('provider', 'MANUAL_QR')
            ->where(function ($query) use ($references): void {
                $query->whereIn('qr_payload', $references)
                    ->orWhereIn('provider_metadata->payment_reference', $references);
            })
            ->get();
    }

    public function hasBankTransactionId(string $transactionId): bool
    {
        return BankTransferTransaction::query()
            ->where('bank_transaction_id', $transactionId)
            ->exists();
    }
}
