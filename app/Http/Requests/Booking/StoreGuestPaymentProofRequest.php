<?php

namespace App\Http\Requests\Booking;

class StoreGuestPaymentProofRequest extends StoreGuestBookingRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'payment_reference' => ['required', 'string', 'alpha_dash', 'max:32'],
            'status_token' => ['required', 'string', 'size:64', 'regex:/^[A-Za-z0-9]+$/'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
