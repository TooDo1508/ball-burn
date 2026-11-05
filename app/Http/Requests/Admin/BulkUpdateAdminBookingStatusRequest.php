<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateAdminBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'booking_order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'booking_order_ids.*' => ['required', 'integer', 'distinct', 'exists:booking_orders,id'],
            'status' => ['required', Rule::in(['PENDING_PAYMENT', 'PAID'])],
        ];
    }
}
