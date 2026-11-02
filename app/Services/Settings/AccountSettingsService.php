<?php

namespace App\Services\Settings;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\Features;

class AccountSettingsService
{
    public function __construct(private readonly UserRepository $users) {}

    public function profileData(User $user, mixed $status): array
    {
        return [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $status,
        ];
    }

    public function updateProfile(User $user, array $attributes): User
    {
        if (($attributes['email'] ?? $user->email) !== $user->email) {
            $attributes['email_verified_at'] = null;
        }

        return $this->users->saveProfile($user, $attributes);
    }

    public function securityData(User $user): array
    {
        $data = ['canManageTwoFactor' => Features::canManageTwoFactorAuthentication()];

        if (Features::canManageTwoFactorAuthentication()) {
            $data['twoFactorEnabled'] = $user->hasEnabledTwoFactorAuthentication();
            $data['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return $data;
    }

    public function updatePassword(User $user, string $password): void
    {
        $this->users->updatePassword($user, $password);
    }
}
