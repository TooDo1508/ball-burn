<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'surface_type' => ['nullable', 'string', 'max:80'],
            'court_type' => ['required', 'in:REGULAR,CENTER'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'operating_hours' => ['required', 'array', 'size:7'],
            'operating_hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'operating_hours.*.is_closed' => ['required', 'boolean'],
            'operating_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'price_rules' => ['nullable', 'array', 'max:20'],
            'price_rules.*.day_type' => ['required_with:price_rules', 'in:WEEKDAY,WEEKEND'],
            'price_rules.*.starts_at' => ['required_with:price_rules', 'date_format:H:i'],
            'price_rules.*.ends_at' => ['required_with:price_rules', 'date_format:H:i'],
            'price_rules.*.price_per_hour' => ['required_with:price_rules', 'numeric', 'min:0', 'max:99999999.99'],
            'price_rules.*.effective_from' => ['required_with:price_rules', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $hours = $this->input('operating_hours', []);
            $days = collect($hours)->pluck('day_of_week')->map(fn ($day): int => (int) $day)->sort()->values()->all();
            if ($days !== [1, 2, 3, 4, 5, 6, 7]) {
                $validator->errors()->add('operating_hours', 'Lịch hoạt động phải có đủ 7 ngày trong tuần.');
            }

            foreach ($hours as $index => $hour) {
                if (filter_var($hour['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $opensAt = $hour['opens_at'] ?? null;
                $closesAt = $hour['closes_at'] ?? null;
                if (! $opensAt || ! $closesAt) {
                    $validator->errors()->add("operating_hours.{$index}.opens_at", 'Hãy nhập giờ mở và đóng cửa.');

                    continue;
                }

                if ($opensAt >= $closesAt) {
                    $validator->errors()->add("operating_hours.{$index}.closes_at", 'Giờ đóng cửa phải sau giờ mở cửa trong cùng ngày.');
                }

                if (! $this->isHalfHourBoundary($opensAt) || ! $this->isHalfHourBoundary($closesAt)) {
                    $validator->errors()->add("operating_hours.{$index}.opens_at", 'Giờ hoạt động phải theo bước 30 phút.');
                }
            }

            foreach ($this->input('price_rules', []) as $index => $rule) {
                $startsAt = $rule['starts_at'] ?? '';
                $endsAt = $rule['ends_at'] ?? '';
                if ($startsAt >= $endsAt) {
                    $validator->errors()->add("price_rules.{$index}.ends_at", 'Giờ kết thúc của mức giá phải sau giờ bắt đầu.');
                }
                if (! $this->isHalfHourBoundary($startsAt) || ! $this->isHalfHourBoundary($endsAt)) {
                    $validator->errors()->add("price_rules.{$index}.starts_at", 'Khung giá phải theo bước 30 phút.');
                }
            }
        }];
    }

    private function isHalfHourBoundary(string $time): bool
    {
        return in_array(substr($time, -2), ['00', '30'], true);
    }
}
