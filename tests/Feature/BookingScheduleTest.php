<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\Court;
use App\Models\Payment;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Payments\PaymentReceiptMappingService;
use Carbon\CarbonImmutable;
use Database\Seeders\CourtCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_uses_order_status_and_strict_deadlines_with_half_open_intervals(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 01:00:00', 'UTC'));
        $this->seed(CourtCatalogSeeder::class);
        $court = Court::query()->firstOrFail();
        $paid = $this->booking($court, 'PAID', null);
        $pending = $this->booking($court, 'PENDING_PAYMENT', now()->addSecond());
        $this->booking($court, 'PENDING_PAYMENT', now());
        $this->booking($court, 'PENDING_PAYMENT', now()->subSecond());
        $this->booking($court, 'PENDING_PAYMENT', null);
        $endBoundary = $this->booking($court, 'PAID', null);
        $endBoundary->update(['starts_at' => '2026-11-09 03:00:00', 'ends_at' => '2026-11-09 03:30:00']);

        $bookings = app(CourtRepository::class)->bookingsBetween(
            CarbonImmutable::parse('2026-11-09 02:00:00', 'UTC'),
            CarbonImmutable::parse('2026-11-09 03:00:00', 'UTC'),
        );

        $this->assertEqualsCanonicalizing([$paid->id, $pending->id], $bookings->modelKeys());
        foreach ($bookings as $booking) {
            $this->assertTrue($booking->relationLoaded('order'), 'Rendering the schedule must not query one order per booking.');
        }
    }

    public function test_public_grid_keeps_each_courts_bookings_and_tier_prices_separate(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 01:00:00', 'UTC'));
        $this->seed(CourtCatalogSeeder::class);
        $courts = Court::query()->orderBy('id')->get();
        $this->booking($courts[0], 'PAID', null);

        $this->getJson('/booking/data?date=2026-11-09')
            ->assertOk()
            ->assertJsonCount(8, 'data.courts')
            ->assertJsonCount(36, 'data.grid_time_slots')
            ->assertJsonPath("data.court_slots.{$courts[0]->id}.8.status", 'booked')
            ->assertJsonPath("data.court_slots.{$courts[1]->id}.8.status", 'available')
            ->assertJsonPath("data.court_slots.{$courts[0]->id}.8.amount", '125000.00');
    }

    public function test_guest_status_exposes_the_current_order_without_customer_details(): void
    {
        $this->seed(CourtCatalogSeeder::class);
        $booking = $this->booking(Court::query()->firstOrFail(), 'PENDING_PAYMENT', now()->addMinutes(5));
        $booking->order->update(['status_token_hash' => hash('sha256', str_repeat('a', 64))]);
        $this->getJson('/booking/status/'.str_repeat('a', 64).'/data')
            ->assertOk()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.bookings.0.court', $booking->court->name)
            ->assertJsonPath('data.payment_submitted', false)
            ->assertJsonMissingPath('data.customer_email')
            ->assertJsonMissingPath('data.status_token_hash')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_receipt_mapping_uses_real_payment_fields_after_legacy_status_was_removed(): void
    {
        $this->seed(CourtCatalogSeeder::class);
        $booking = $this->booking(Court::query()->firstOrFail(), 'PENDING_PAYMENT', now()->addMinutes(5));
        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'MANUAL_QR',
            'amount' => '75000.00',
            'currency' => 'VND',
            'qr_payload' => 'BBPAYREGRESSION',
        ]);
        $fields = ['payment_reference' => 'BBPAYREGRESSION', 'amount' => '75000', 'currency' => 'VND'];
        $result = app(PaymentReceiptMappingService::class)->map($fields);
        $this->assertSame('MATCHED', $result['status'], json_encode($result));
        $this->assertFalse($result['payment_can_be_marked_paid']);
        $this->assertSame('PENDING_PAYMENT', $booking->order->fresh()->status);

        $payment->update(['paid_at' => now()]);
        $result = app(PaymentReceiptMappingService::class)->map($fields);
        $this->assertContains('payment_not_pending', $result['reasons']);
    }

    private function booking(Court $court, string $status, mixed $deadline): Booking
    {
        $order = BookingOrder::query()->create([
            'booking_code' => 'ORDER-'.bin2hex(random_bytes(6)),
            'status' => $status,
            'status_token_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'customer_name' => 'Test Guest',
            'customer_email' => 'guest@example.test',
            'customer_phone' => '0900000000',
            'payment_deadline_at' => $deadline,
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);

        return Booking::query()->create([
            'booking_order_id' => $order->id,
            'booking_code' => 'SEG-'.bin2hex(random_bytes(6)),
            'court_id' => $court->id,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'starts_at' => '2026-11-09 02:00:00',
            'ends_at' => '2026-11-09 02:30:00',
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);
    }
}
