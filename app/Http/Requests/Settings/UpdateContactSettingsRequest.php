<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'contact.email' => ['required', 'email:rfc', 'max:255'],
            'contact.payment_account_number' => ['required', 'string', 'regex:/^\d{6,19}$/'],
            'contact.phone' => ['required', 'string', 'max:30'],
            'contact.address' => ['required', 'string', 'max:255'],
            'contact.map_coordinates' => ['required', 'string', 'max:64', 'regex:/^-?\d{1,2}\.\d+,\s*-?\d{1,3}\.\d+$/'],
            'contact.business_hours' => ['required', 'string', 'max:120'],
            'contact.venue_description' => ['nullable', 'string', 'max:250'],
            'contact.map_url' => ['nullable', 'url', 'max:1000'],
            'contact.facebook_url' => ['nullable', 'url', 'max:1000'],
            'contact.instagram_url' => ['nullable', 'url', 'max:1000'],
        ];
    }
}
