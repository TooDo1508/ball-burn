<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransferTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'bank_transaction_id' => ['required', 'string', 'max:191', 'unique:bank_transfer_transactions,bank_transaction_id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'currency' => ['required', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'transaction_at' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
