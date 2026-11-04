<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourtClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $startsAt = (string) $this->input('starts_at');
            $endsAt = (string) $this->input('ends_at');
            if ($startsAt >= $endsAt) {
                $validator->errors()->add('ends_at', 'Giờ kết thúc phải sau giờ bắt đầu.');
            }

            foreach (['starts_at' => $startsAt, 'ends_at' => $endsAt] as $field => $time) {
                if (! in_array(substr($time, -2), ['00', '30'], true)) {
                    $validator->errors()->add($field, 'Thời gian khóa phải theo bước 30 phút.');
                }
            }
        }];
    }
}
