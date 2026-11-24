<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class LookupGuestBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'booking_code' => strtoupper(trim((string) $this->input('booking_code'))),
            'customer_phone' => trim((string) $this->input('customer_phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'booking_code' => ['required', 'string', 'regex:/^BB-\d{8}-[A-Z0-9]{6}$/'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[+\d\s().-]+$/'],
        ];
    }
}
