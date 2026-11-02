<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_see_login_and_cannot_access_admin(): void
    {
        $this->get('/admin/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/settings/profile')->assertRedirect('/admin/login');
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', [])->assertNotFound();
    }

    public function test_admin_and_staff_can_login_and_logout(): void
    {
        foreach (['admin', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
            $this->assertAuthenticatedAs($user);
            $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
            $this->get('/admin/settings/profile')->assertOk();
            $this->post('/admin/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_unprivileged_users_and_incorrect_passwords_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->get('/admin/settings/profile')->assertForbidden();
        $this->patch('/admin/settings/profile', ['name' => 'Changed', 'email' => $user->email])->assertForbidden();
    }

    public function test_invalid_password_is_rejected_and_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_password_reset_uses_a_valid_token_and_new_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'admin']);
        $this->post('/admin/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::broker()->createToken($user);
        $password = 'UpdatedPassword123';
        $this->post('/admin/reset-password', [
            'email' => $user->email, 'token' => $token,
            'password' => $password, 'password_confirmation' => $password,
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_two_factor_users_are_challenged_before_authentication(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin/two-factor-challenge');
        $this->assertGuest();
        $this->post('/admin/two-factor-challenge', ['recovery_code' => 'recovery-code'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_update_cannot_escalate_role_or_delete_account(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $this->actingAs($user)->patch('/admin/settings/profile', [
            'name' => 'Staff', 'email' => $user->email, 'role' => 'admin',
        ])->assertSessionHasNoErrors();
        $this->assertSame('staff', $user->fresh()->role);
        $this->delete('/admin/settings/profile')->assertStatus(405);
    }

    public function test_admin_creation_requires_strong_password_and_unique_email(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (at least 12 characters, mixed case and numbers)', 'SecurePassword123')
            ->expectsQuestion('Confirm password', 'SecurePassword123')
            ->assertSuccessful();
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('SecurePassword123', $user->password));
    }

    public function test_public_pages_remain_accessible_without_authentication(): void
    {
        foreach (['/', '/about', '/blog', '/booking', '/contact'] as $path) {
            $this->get($path)->assertOk()->assertViewIs('welcome');
        }
    }

    public function test_admin_creation_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Admin')
            ->expectsQuestion('Email', 'existing@example.test')
            ->expectsQuestion('Password (at least 12 characters, mixed case and numbers)', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertNull(User::first()->role);
    }

    public function test_invalid_reset_token_cannot_change_password(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->post('/admin/reset-password', [
            'email' => $user->email, 'token' => 'invalid',
            'password' => 'UpdatedPassword123', 'password_confirmation' => 'UpdatedPassword123',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
