<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexCourtScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() === true;
    }

    public function rules(): array
    {
        return ['date' => ['nullable', 'date_format:Y-m-d']];
    }
}
