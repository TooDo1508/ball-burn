<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminBookingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('admin.bookings.cancellations')) {
            $this->merge(['status' => 'CANCELLED']);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'status' => ['nullable', Rule::in(['PENDING_PAYMENT', 'PAID', 'CANCELLED'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
        ];
    }
}
