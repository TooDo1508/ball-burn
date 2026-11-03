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
            'contact.email' => ['nullable', 'email:rfc', 'max:255'],
            'contact.phone' => ['required', 'string', 'max:30'],
            'contact.address' => ['required', 'string', 'max:255'],
            'contact.business_hours' => ['required', 'string', 'max:120'],
            'contact.venue_description' => ['nullable', 'string', 'max:250'],
            'contact.map_url' => ['nullable', 'url', 'max:1000'],
            'contact.facebook_url' => ['nullable', 'url', 'max:1000'],
            'contact.instagram_url' => ['nullable', 'url', 'max:1000'],
        ];
    }
}
