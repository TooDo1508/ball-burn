<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', Rule::in(array_keys(User::ROLES))],
            'status' => ['nullable', Rule::in(['ACTIVE', 'LOCKED', 'DISABLED'])],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }
}
