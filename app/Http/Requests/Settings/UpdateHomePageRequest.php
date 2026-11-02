<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'array'],
            'content.site_name' => ['required', 'string', 'max:100'],
            'content.meta_title' => ['required', 'string', 'max:70'],
            'content.meta_description' => ['required', 'string', 'max:160'],
            'content.hero_title' => ['required', 'string', 'max:160'],
            'content.hero_description' => ['required', 'string', 'max:500'],
            'content.hero_image' => ['required', 'url', 'max:1000'],
            'content.about_title' => ['required', 'string', 'max:160'],
            'content.about_text' => ['required', 'string', 'max:1000'],
            'content.courts_title' => ['required', 'string', 'max:160'],
            'content.courts_text' => ['required', 'string', 'max:500'],
            'content.bistro_title' => ['required', 'string', 'max:160'],
            'content.bistro_text' => ['required', 'string', 'max:500'],
            'content.booking_title' => ['required', 'string', 'max:160'],
            'content.og_image' => ['required', 'url', 'max:1000'],
        ];
    }
}
