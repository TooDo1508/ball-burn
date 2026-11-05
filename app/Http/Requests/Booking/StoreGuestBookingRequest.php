<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGuestBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'hold_token' => ['required', 'regex:/^[A-Za-z0-9]{64}$/'],
            'slots' => ['required', 'array', 'min:1', 'max:64'],
            'slots.*.court_id' => ['required', 'integer', 'exists:courts,id'],
            'slots.*.starts_at' => ['required', 'date_format:H:i'],
            'slots.*.ends_at' => ['required', 'date_format:H:i'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email:rfc', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $slots = $this->input('slots', []);
            if (! is_array($slots)) {
                $slots = [];
            }
            $totalHalfHours = 0;
            foreach ($slots as $index => $slot) {
                if (! is_array($slot)) {
                    continue;
                }
                $startsAt = (string) ($slot['starts_at'] ?? '');
                $endsAt = (string) ($slot['ends_at'] ?? '');
                if ($startsAt >= $endsAt) {
                    $validator->errors()->add("slots.{$index}.ends_at", 'Giờ kết thúc phải sau giờ bắt đầu.');
                }

                foreach (['starts_at' => $startsAt, 'ends_at' => $endsAt] as $field => $time) {
                    if (! in_array(substr($time, -2), ['00', '30'], true)) {
                        $validator->errors()->add("slots.{$index}.{$field}", 'Thời gian đặt phải theo bước 30 phút.');
                    }
                }

                if (preg_match('/^\d{2}:\d{2}$/', $startsAt) && preg_match('/^\d{2}:\d{2}$/', $endsAt) && $startsAt < $endsAt) {
                    $minutes = ((int) substr($endsAt, 0, 2) * 60 + (int) substr($endsAt, 3, 2))
                        - ((int) substr($startsAt, 0, 2) * 60 + (int) substr($startsAt, 3, 2));
                    $totalHalfHours += (int) ceil($minutes / 30);
                }
            }

            if ($totalHalfHours > 64) {
                $validator->errors()->add('slots', 'Mỗi lần đặt có thể chọn tối đa 64 khung giờ 30 phút.');
            }

            for ($left = 0; $left < count($slots); $left++) {
                for ($right = $left + 1; $right < count($slots); $right++) {
                    $first = $slots[$left];
                    $second = $slots[$right];
                    if (! is_array($first) || ! is_array($second)) {
                        continue;
                    }
                    if ((int) ($first['court_id'] ?? 0) !== (int) ($second['court_id'] ?? 0)) {
                        continue;
                    }
                    if (($first['starts_at'] ?? '') < ($second['ends_at'] ?? '') && ($first['ends_at'] ?? '') > ($second['starts_at'] ?? '')) {
                        $validator->errors()->add("slots.{$right}.starts_at", 'Các khung giờ trên cùng sân không được trùng nhau.');
                    }
                }
            }
        }];
    }
}
