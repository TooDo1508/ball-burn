<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexAdminDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(['today', '7', '30', 'custom'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('range') !== 'custom' || ! $this->filled('from') || ! $this->filled('to')) {
                return;
            }

            if ($validator->errors()->has('from') || $validator->errors()->has('to')) {
                return;
            }

            $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('from'), $timezone);
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('to'), $timezone);

            if ($from->diffInDays($to) > 89) {
                $validator->errors()->add('to', 'Khoảng thời gian tùy chọn tối đa là 90 ngày.');
            }
        });
    }
}
