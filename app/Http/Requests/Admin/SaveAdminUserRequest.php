<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? ($this->user()?->can('update', $target) ?? false)
            : ($this->user()?->can('create', User::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $roles = array_keys(array_filter(User::ROLES, fn (int $level): bool => $level <= $this->user()->roleLevel()));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($target instanceof User ? $target->id : null)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9().\s-]+$/'],
            'role' => ['required', Rule::in($roles)],
            'account_status' => ['required', Rule::in(['ACTIVE', 'LOCKED', 'DISABLED'])],
            'password' => [$target instanceof User ? 'nullable' : 'required', 'string', 'max:255', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'họ tên', 'email' => 'email', 'phone' => 'số điện thoại', 'role' => 'vai trò', 'account_status' => 'trạng thái', 'password' => 'mật khẩu'];
    }
}
