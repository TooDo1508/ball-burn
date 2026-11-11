<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function canAccessAdmin(): bool
    {
        return $this->is_active && $this->account_status === 'ACTIVE' && in_array(strtoupper((string) $this->role), ['ADMIN', 'MANAGER', 'STAFF'], true);
    }

    protected $attributes = ['account_status' => 'ACTIVE', 'is_active' => true];

    public const ROLES = ['ADMIN' => 3, 'MANAGER' => 2, 'STAFF' => 1];

    public function roleLevel(): int
    {
        return self::ROLES[strtoupper((string) $this->role)] ?? 0;
    }

    public function canManageBookingDestructively(): bool
    {
        return $this->canAccessAdmin() && $this->roleLevel() >= self::ROLES['MANAGER'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
