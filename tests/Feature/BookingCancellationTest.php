<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Models\User;
use App\Repositories\Contracts\CourtRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_an_order_and_view_it_in_the_cancellation_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        [$court, $booking] = $this->createBooking();
        $startsAt = CarbonImmutable::parse($booking->starts_at);
        $endsAt = CarbonImmutable::parse($booking->ends_at);

        $this->actingAs($admin)->put("/admin/bookings/{$booking->id}/status", [
            'status' => 'CANCELLED',
            'note' => 'Sân cần được sửa chữa.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('CANCELLED', $booking->order->fresh()->status);
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'old_status' => 'PENDING_PAYMENT',
            'new_status' => 'CANCELLED',
            'changed_by' => $admin->id,
            'change_source' => 'ADMIN',
            'note' => 'Sân cần được sửa chữa.',
        ]);
        $this->assertCount(0, app(CourtRepository::class)->bookingsBetween($startsAt, $endsAt));

        $this->get('/admin/bookings/cancellations')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/bookings/Index')
            ->where('filters.status', 'CANCELLED')
            ->where('bookings.data.0.status', 'CANCELLED')
            ->where('bookings.data.0.booking_code', $booking->order->booking_code));
    }

    public function test_cancellation_requires_a_reason_and_cancelled_orders_cannot_be_reopened(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        [, $booking] = $this->createBooking();

        $this->actingAs($admin)->put("/admin/bookings/{$booking->id}/status", [
            'status' => 'CANCELLED',
        ])->assertSessionHasErrors('note');

        $booking->order->update(['status' => 'CANCELLED']);
        $this->put("/admin/bookings/{$booking->id}/status", [
            'status' => 'PAID',
        ])->assertSessionHasErrors('status');
    }

    /** @return array{Court, Booking} */
    private function createBooking(): array
    {
        $court = Court::query()->create([
            'name' => 'Sân cần sửa chữa',
            'slug' => 'san-can-sua-chua',
            'price_per_hour' => 150000,
            'currency' => 'VND',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $order = BookingOrder::query()->create([
            'booking_code' => 'BB-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => 'PENDING_PAYMENT',
            'status_token_hash' => hash('sha256', random_bytes(32)),
            'customer_name' => 'Khách BALLBURN',
            'customer_email' => 'guest@example.test',
            'customer_phone' => '0900000000',
            'payment_deadline_at' => now()->addMinutes(5),
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);
        $startsAt = now()->addDays(2)->startOfHour();
        $booking = Booking::query()->create([
            'booking_order_id' => $order->id,
            'booking_code' => 'SEG-'.strtoupper(bin2hex(random_bytes(5))),
            'court_id' => $court->id,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);

        return [$court, $booking];
    }
}
