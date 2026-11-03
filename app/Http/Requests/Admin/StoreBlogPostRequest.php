<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240', 'alpha_dash'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:20000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover_image' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['DRAFT', 'PUBLISHED', 'ARCHIVED'])],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'distinct'],
        ];
    }
}
