<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_role_hierarchy_controls_creation_and_updates_for_every_role(): void
    {
        foreach (array_keys(User::ROLES) as $role) {
            $actor = User::factory()->create(['role' => $role, 'is_active' => true]);
            foreach (User::ROLES as $assigned => $level) {
                $data = $this->payload($assigned);
                $response = $this->actingAs($actor)->post('/admin/users', $data);
                if ($level > $actor->roleLevel()) {
                    $response->assertSessionHasErrors('role');
                    $this->assertDatabaseMissing('users', ['email' => $data['email']]);
                } else {
                    $response->assertRedirect('/admin/users')->assertSessionHasNoErrors();
                    $created = User::where('email', $data['email'])->firstOrFail();
                    $this->assertSame($assigned, $created->role);
                    $this->assertTrue(Hash::check($data['password'], $created->password));
                }
                $target = User::factory()->create(['role' => $assigned, 'is_active' => true]);
                $response = $this->put('/admin/users/'.$target->id, $this->payload($assigned, ['email' => $target->email, 'name' => 'Đã cập nhật']));
                if ($level > $actor->roleLevel()) {
                    $response->assertForbidden();
                } else {
                    $response->assertSessionHasNoErrors();
                }
                $this->assertSame($level > $actor->roleLevel() ? $target->name : 'Đã cập nhật', $target->fresh()->name);
            }
        }
    }

    public function test_lower_roles_cannot_promote_themselves_or_other_accounts(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $staff = User::factory()->create(['role' => 'STAFF']);
        foreach ([$manager, $staff] as $actor) {
            $this->actingAs($actor)->put('/admin/users/'.$actor->id, $this->payload('ADMIN', ['email' => $actor->email]))->assertSessionHasErrors('role');
            $this->assertSame($actor->role, $actor->fresh()->role);
        }
        $this->actingAs($manager)->put('/admin/users/'.$staff->id, $this->payload('ADMIN', ['email' => $staff->email]))->assertSessionHasErrors('role');
    }

    public function test_deletion_respects_hierarchy_and_prevents_self_deletion(): void
    {
        foreach (User::ROLES as $role => $actorLevel) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->delete('/admin/users/'.$actor->id)->assertForbidden();
            foreach (User::ROLES as $targetRole => $targetLevel) {
                $target = User::factory()->create(['role' => $targetRole]);
                $response = $this->delete('/admin/users/'.$target->id);
                if ($actorLevel < $targetLevel) {
                    $response->assertForbidden();
                    $this->assertModelExists($target);
                } else {
                    $response->assertSessionHasNoErrors();
                    $this->assertModelMissing($target);
                }
            }
        }
    }

    public function test_self_lock_and_demotion_are_rejected_and_admin_access_remains(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        foreach ([['account_status' => 'LOCKED'], ['account_status' => 'DISABLED'], ['role' => 'STAFF']] as $changes) {
            $this->actingAs($admin)->put('/admin/users/'.$admin->id, $this->payload('ADMIN', ['email' => $admin->email, ...$changes]))->assertSessionHasErrors('account_status');
            $this->assertTrue($admin->fresh()->canAccessAdmin());
            $this->assertSame('ADMIN', $admin->fresh()->role);
        }
    }

    public function test_validation_and_password_preservation_and_safe_fields(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $target = User::factory()->create(['role' => 'STAFF']);
        $this->actingAs($admin)->post('/admin/users', $this->payload('STAFF', ['email' => $target->email, 'password' => 'weak', 'password_confirmation' => 'different']))->assertSessionHasErrors(['email', 'password']);
        $this->post('/admin/users', $this->payload('OWNER', ['account_status' => 'UNKNOWN', 'phone' => '<script>']))->assertSessionHasErrors(['role', 'account_status', 'phone']);
        $oldHash = $target->password;
        $this->put('/admin/users/'.$target->id, $this->payload('MANAGER', ['email' => $target->email, 'password' => '', 'password_confirmation' => '', 'is_active' => false, 'two_factor_secret' => 'injected']))->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertSame($oldHash, $target->password);
        $this->assertNull($target->two_factor_secret);
        $this->assertTrue($target->is_active);
        $this->assertSame('MANAGER', $target->role);
    }

    public function test_locked_and_disabled_users_cannot_login_or_use_existing_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        foreach (['LOCKED', 'DISABLED'] as $status) {
            $target = User::factory()->create(['role' => 'MANAGER']);
            $this->actingAs($admin)->put('/admin/users/'.$target->id, $this->payload('MANAGER', ['email' => $target->email, 'account_status' => $status]))->assertSessionHasNoErrors();
            $this->assertFalse($target->fresh()->is_active);
            $this->actingAs($target->fresh())->get('/admin/users')->assertForbidden();
            auth()->logout();
            $this->post('/admin/login', ['email' => $target->email, 'password' => 'StrongPassword123'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_reactivation_and_manager_login_work(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $target = User::factory()->create(['role' => 'MANAGER', 'is_active' => false, 'account_status' => 'LOCKED']);
        $this->actingAs($admin)->put('/admin/users/'.$target->id, $this->payload('MANAGER', ['email' => $target->email]))->assertSessionHasNoErrors();
        auth()->logout();
        $this->post('/admin/login', ['email' => $target->email, 'password' => 'StrongPassword123'])->assertRedirect('/admin');
        foreach (['/admin', '/admin/users', '/admin/courts', '/admin/news', '/admin/contact-messages', '/admin/settings/contact'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_listing_filters_phone_role_status_and_paginates_without_sensitive_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(11)->create(['role' => 'STAFF']);
        $target = User::factory()->create(['name' => 'Nguyễn Quản Lý', 'role' => 'MANAGER', 'phone' => '0901234567', 'account_status' => 'LOCKED', 'is_active' => false]);
        $this->actingAs($admin)->get('/admin/users')->assertInertia(fn (Assert $page) => $page->has('users.data', 10)->where('users.total', 13)->where('stats.total', 13)->where('stats.locked', 1)->where('assignableRoles', ['ADMIN', 'MANAGER', 'STAFF'])->missing('users.data.0.password')->missing('users.data.0.two_factor_secret'));
        $this->get('/admin/users?search=090123&role=MANAGER&status=LOCKED')->assertInertia(fn (Assert $page) => $page->where('users.total', 1)->where('users.data.0.id', $target->id)->where('users.data.0.can_edit', true));
        $this->actingAs($target->forceFill(['account_status' => 'ACTIVE', 'is_active' => true]))->get('/admin/users?role=ADMIN')->assertInertia(fn (Assert $page) => $page->where('users.data.0.can_edit', false)->where('users.data.0.can_delete', false)->where('assignableRoles', ['MANAGER', 'STAFF']));
    }

    public function test_staff_cannot_cancel_or_delete_bookings_through_direct_or_bulk_requests(): void
    {
        $staff = User::factory()->create(['role' => 'STAFF']);
        $booking = $this->booking();
        $this->actingAs($staff)->put('/admin/bookings/'.$booking->id.'/status', ['status' => 'CANCELLED', 'note' => 'Không được phép'])->assertForbidden();
        $this->delete('/admin/bookings/bulk', ['booking_order_ids' => [$booking->booking_order_id]])->assertForbidden();
        $this->put('/admin/bookings/bulk/status', ['booking_order_ids' => [$booking->booking_order_id], 'status' => 'CANCELLED'])->assertSessionHasErrors('status');
        $this->assertSame('PENDING_PAYMENT', $booking->order->fresh()->status);
        $this->get('/admin/bookings')->assertInertia(fn (Assert $page) => $page->where('canDeleteBookings', false)->where('bookings.data.0.detail.status_options', [['value' => 'PAID', 'label' => 'Đã thanh toán']]));
        $this->put('/admin/bookings/'.$booking->id.'/status', ['status' => 'PAID'])->assertSessionHasNoErrors();
        $this->assertSame('PAID', $booking->order->fresh()->status);
    }

    public function test_managers_and_admins_can_cancel_and_delete_bookings(): void
    {
        foreach (['ADMIN', 'MANAGER'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $booking = $this->booking();
            $this->actingAs($actor)->put('/admin/bookings/'.$booking->id.'/status', ['status' => 'CANCELLED', 'note' => 'Sửa sân'])->assertSessionHasNoErrors();
            $this->assertSame('CANCELLED', $booking->order->fresh()->status);
            $pending = $this->booking();
            $this->delete('/admin/bookings/bulk', ['booking_order_ids' => [$pending->booking_order_id]])->assertSessionHasNoErrors();
            $this->assertModelMissing($pending);
        }
    }

    public function test_guests_and_non_staff_accounts_cannot_mutate_users(): void
    {
        $target = User::factory()->create(['role' => 'STAFF']);
        $this->post('/admin/users', $this->payload('STAFF'))->assertRedirect('/admin/login');
        $outsider = User::factory()->create(['role' => 'USER']);
        $this->actingAs($outsider)->get('/admin/users')->assertForbidden();
        $this->post('/admin/users', $this->payload('STAFF'))->assertForbidden();
        $this->put('/admin/users/'.$target->id, $this->payload('STAFF'))->assertForbidden();
        $this->delete('/admin/users/'.$target->id)->assertForbidden();
    }

    private function payload(string $role, array $overrides = []): array
    {
        return [...['name' => 'Nguyễn Minh An', 'email' => fake()->unique()->safeEmail(), 'phone' => '0901234567', 'role' => $role, 'account_status' => 'ACTIVE', 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123'], ...$overrides];
    }

    private function booking(): Booking
    {
        $court = Court::create(['name' => 'Sân kiểm thử', 'slug' => 'test-'.bin2hex(random_bytes(4)), 'price_per_hour' => 150000, 'currency' => 'VND', 'is_active' => true, 'sort_order' => 1]);
        $order = BookingOrder::create(['booking_code' => 'BB-'.bin2hex(random_bytes(5)), 'status' => 'PENDING_PAYMENT', 'status_token_hash' => hash('sha256', random_bytes(32)), 'customer_name' => 'Khách', 'payment_deadline_at' => now()->addMinutes(5), 'base_amount' => 75000, 'discount_amount' => 0, 'total_amount' => 75000, 'currency' => 'VND']);

        return Booking::create(['booking_order_id' => $order->id, 'booking_code' => 'SEG-'.bin2hex(random_bytes(5)), 'court_id' => $court->id, 'customer_name' => 'Khách', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(30), 'base_amount' => 75000, 'discount_amount' => 0, 'total_amount' => 75000, 'currency' => 'VND']);
    }
}
