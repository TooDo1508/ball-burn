<?php

namespace Tests\Feature;

use App\Models\BankTransferTransaction;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        CarbonImmutable::setTestNow('2026-11-03 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_view_filtered_booking_list_and_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $booking = $this->createBooking($this->createCourt(), 'CONFIRMED', '2026-11-04 10:00:00', null);

        $this->actingAs($admin)->get('/admin/bookings?search='.$booking->booking_code)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/bookings/Index')
                ->where('bookings.total', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
                ->where('bookings.data.0.payment_status', 'UNPAID'));

        $this->actingAs($admin)->get("/admin/bookings/{$booking->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/bookings/Show')
                ->where('booking.booking_code', $booking->booking_code)
                ->has('booking.payments', 0));
    }

    public function test_admin_can_reconcile_a_verified_transfer_and_confirm_a_live_pending_booking(): void
    {
        $admin = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $booking = $this->createBooking($this->createCourt(), 'PENDING_PAYMENT', '2026-11-04 10:00:00', '2026-11-03 09:04:59');

        $this->actingAs($admin)->post('/admin/bank-transfers', [
            'bank_transaction_id' => 'BANK-REF-0001',
            'amount' => '75000.00',
            'currency' => 'VND',
            'transaction_at' => '2026-11-03T09:02',
            'transfer_description' => '',
        ])->assertSessionHasNoErrors();

        $transfer = BankTransferTransaction::query()->firstOrFail();
        $this->actingAs($admin)->post("/admin/bank-transfers/{$transfer->id}/match", [
            'booking_id' => $booking->id,
            'review_note' => 'Đã kiểm tra giao dịch trong app ngân hàng.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('CONFIRMED', $booking->refresh()->status);
        $this->assertSame('MATCHED', $transfer->refresh()->status);
        $this->assertSame('PAID', Payment::query()->where('booking_id', $booking->id)->firstOrFail()->status);
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'old_status' => 'PENDING_PAYMENT',
            'new_status' => 'CONFIRMED',
            'changed_by' => $admin->id,
            'change_source' => 'ADMIN',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'bank_transfer.matched',
            'user_id' => $admin->id,
            'entity_id' => (string) $transfer->id,
        ]);
    }

    public function test_late_transfer_is_recorded_without_reconfirming_expired_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $booking = $this->createBooking($this->createCourt(), 'PENDING_PAYMENT', '2026-11-04 10:00:00', '2026-11-03 08:59:59');
        $this->actingAs($admin)->post('/admin/bank-transfers', [
            'bank_transaction_id' => 'BANK-REF-LATE',
            'amount' => '75000.00',
            'currency' => 'VND',
            'transaction_at' => '2026-11-03T08:58',
        ])->assertSessionHasNoErrors();
        $transfer = BankTransferTransaction::query()->firstOrFail();

        $this->actingAs($admin)->post("/admin/bank-transfers/{$transfer->id}/match", ['booking_id' => $booking->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('EXPIRED', $booking->refresh()->status);
        $this->assertSame('LATE_PAYMENT', $transfer->refresh()->status);
        $this->assertSame('PAID', Payment::query()->where('booking_id', $booking->id)->firstOrFail()->status);
    }

    public function test_amount_mismatch_stays_in_review_queue_and_does_not_confirm_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $booking = $this->createBooking($this->createCourt(), 'PENDING_PAYMENT', '2026-11-04 10:00:00', '2026-11-03 09:04:59');
        $this->actingAs($admin)->post('/admin/bank-transfers', [
            'bank_transaction_id' => 'BANK-REF-SHORT',
            'amount' => '70000.00',
            'currency' => 'VND',
            'transaction_at' => '2026-11-03T09:02',
        ])->assertSessionHasNoErrors();
        $transfer = BankTransferTransaction::query()->firstOrFail();

        $this->actingAs($admin)->post("/admin/bank-transfers/{$transfer->id}/match", ['booking_id' => $booking->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('PENDING_PAYMENT', $booking->refresh()->status);
        $this->assertSame('NEEDS_REVIEW', $transfer->refresh()->status);
        $this->assertNull($transfer->booking_id);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_regular_user_cannot_access_booking_management_or_record_transfers(): void
    {
        $user = User::factory()->create(['role' => 'USER', 'is_active' => true]);
        $this->actingAs($user)->get('/admin/bookings')->assertForbidden();
        $this->actingAs($user)->post('/admin/bank-transfers', [])->assertForbidden();
    }

    private function createCourt(): Court
    {
        return Court::query()->create([
            'name' => 'Sân đối soát',
            'slug' => 'san-doi-soat',
            'price_per_hour' => 150000,
            'currency' => 'VND',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createBooking(Court $court, string $status, string $startsAt, ?string $deadline): Booking
    {
        $timezone = (string) config('venue.timezone');

        return Booking::query()->create([
            'booking_code' => 'BB-'.strtoupper(bin2hex(random_bytes(4))),
            'court_id' => $court->id,
            'customer_name' => 'Khách BALLBURN',
            'customer_email' => 'guest@example.test',
            'starts_at' => CarbonImmutable::parse($startsAt, $timezone)->utc(),
            'ends_at' => CarbonImmutable::parse($startsAt, $timezone)->addMinutes(30)->utc(),
            'status' => $status,
            'payment_deadline_at' => $deadline,
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);
    }
}
