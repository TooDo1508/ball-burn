<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class SePayWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'gateway' => ['required', 'string', 'max:50'],
            'transactionDate' => ['required', 'date_format:Y-m-d H:i:s'],
            'accountNumber' => ['required', 'string', 'max:100'],
            'subAccount' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'max:500'],
            'transferType' => ['required', 'in:in,out'],
            'transferAmount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'referenceCode' => ['nullable', 'string', 'max:191'],
        ];
    }
}
