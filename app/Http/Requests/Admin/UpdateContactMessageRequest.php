<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['NEW', 'IN_PROGRESS', 'RESOLVED', 'SPAM'])],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
