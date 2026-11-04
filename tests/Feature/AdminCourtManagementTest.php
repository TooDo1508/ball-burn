<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CourtCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminCourtManagementTest extends TestCase
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

    public function test_admin_can_create_and_update_a_court_with_weekly_hours_and_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->assertTrue($admin->canAccessAdmin());
        $this->assertTrue(Gate::forUser($admin)->allows('create', Court::class));

        $this->actingAs($admin)->post('/admin/courts', $this->courtPayload())
            ->assertSessionHasNoErrors();

        $court = Court::query()->firstOrFail();
        $this->assertSame('san-trung-tam', $court->slug);
        $this->assertSame('150000.00', $court->price_per_hour);
        $this->assertCount(7, $court->operatingHours()->get());

        $payload = $this->courtPayload();
        $payload['name'] = 'Sân Trung Tâm Mới';
        $payload['slug'] = 'san-trung-tam-moi';
        $payload['price_per_hour'] = 180000;

        $this->actingAs($admin)->put("/admin/courts/{$court->id}", $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'name' => 'Sân Trung Tâm Mới',
            'price_per_hour' => 180000,
        ]);
        $this->assertDatabaseCount('court_operating_hours', 7);
    }

    public function test_schedule_ignores_expired_pending_bookings(): void
    {
        $admin = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->assertTrue($admin->canAccessAdmin());
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Court::class));
        $court = $this->createCourt();
        $this->createBooking($court, 'CONFIRMED', '09:00', '09:30');
        $this->createBooking($court, 'PENDING_PAYMENT', '10:00', '10:30', '2026-11-03 08:59:59');

        $this->actingAs($admin)->get('/admin/courts?date=2026-11-03')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/courts/Index')
                ->where('stats.booked', 1)
                ->has('bookings', 1)
                ->where('bookings.0.customer_name', 'Khách BALLBURN'));
    }

    public function test_admin_can_lock_an_empty_slot_but_not_a_booked_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->assertTrue($admin->canAccessAdmin());
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Court::class));
        $court = $this->createCourt();
        $this->createBooking($court, 'CONFIRMED', '09:00', '09:30');

        $this->actingAs($admin)->post('/admin/court-closures', [
            'court_id' => $court->id,
            'date' => '2026-11-03',
            'starts_at' => '09:00',
            'ends_at' => '09:30',
            'reason' => 'Bảo trì',
        ])->assertSessionHasErrors('starts_at');

        $this->actingAs($admin)->post('/admin/court-closures', [
            'court_id' => $court->id,
            'date' => '2026-11-03',
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'reason' => 'Bảo trì',
        ])->assertSessionHasNoErrors();

        $closureId = (int) $court->closures()->firstOrFail()->id;
        $this->actingAs($admin)->delete("/admin/court-closures/{$closureId}")
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('court_closures', ['id' => $closureId]);
    }

    public function test_regular_users_cannot_access_court_administration(): void
    {
        $user = User::factory()->create(['role' => 'USER', 'is_active' => true]);
        $this->actingAs($user)->get('/admin/courts')->assertForbidden();
        $this->actingAs($user)->post('/admin/courts', $this->courtPayload())->assertForbidden();
        $this->actingAs($user)->post('/admin/bookings', [])->assertForbidden();
    }

    public function test_catalog_seeder_creates_three_center_and_five_regular_courts_with_full_hours_and_prices(): void
    {
        $this->seed(CourtCatalogSeeder::class);

        $this->assertDatabaseCount('courts', 8);
        $this->assertSame(3, Court::query()->where('court_type', 'CENTER')->count());
        $this->assertSame(5, Court::query()->where('court_type', 'REGULAR')->count());
        $this->assertDatabaseCount('court_operating_hours', 56);
        $this->assertDatabaseCount('court_price_rules', 80);
        $this->assertDatabaseHas('court_operating_hours', [
            'court_id' => Court::query()->where('slug', 'san-1')->value('id'),
            'day_of_week' => 1,
            'opens_at' => '05:00:00',
            'closes_at' => '23:00:00',
            'is_closed' => false,
        ]);
        $this->assertDatabaseHas('court_price_rules', [
            'court_id' => Court::query()->where('slug', 'san-4')->value('id'),
            'day_type' => 'WEEKDAY',
            'starts_at' => '16:00:00',
            'ends_at' => '23:00:00',
            'price_per_hour' => 299000,
        ]);
    }

    public function test_admin_can_book_an_available_range_for_a_customer_but_cannot_double_book_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $court = $this->createCourt();
        $court->priceRules()->create([
            'day_type' => 'WEEKDAY',
            'starts_at' => '05:00:00',
            'ends_at' => '23:00:00',
            'price_per_hour' => 140000,
            'currency' => 'VND',
            'effective_from' => '2026-04-01',
            'is_active' => true,
        ]);
        $payload = [
            'court_id' => $court->id,
            'date' => '2026-11-04',
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'customer_name' => 'Nguyễn Minh An',
            'customer_email' => 'minhan@example.test',
            'customer_phone' => '0901234567',
            'customer_note' => 'Admin đặt hộ qua điện thoại.',
        ];

        $this->actingAs($admin)->post('/admin/bookings', $payload)
            ->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('CONFIRMED', $booking->status);
        $this->assertSame('140000.00', $booking->total_amount);
        $this->assertSame('2026-11-04 03:00:00', $booking->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'new_status' => 'CONFIRMED',
            'changed_by' => $admin->id,
            'change_source' => 'ADMIN',
        ]);

        $this->actingAs($admin)->post('/admin/bookings', $payload)
            ->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('bookings', 1);
    }

    private function createCourt(): Court
    {
        $court = Court::query()->create([
            'name' => 'Sân 1',
            'slug' => 'san-1',
            'price_per_hour' => 150000,
            'currency' => 'VND',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $court->operatingHours()->createMany($this->weeklyHours());

        return $court->load('operatingHours');
    }

    private function createBooking(Court $court, string $status, string $startsAt, string $endsAt, ?string $deadline = null): Booking
    {
        $timezone = (string) config('venue.timezone');

        return Booking::query()->create([
            'booking_code' => uniqid('BB-', true),
            'court_id' => $court->id,
            'customer_name' => 'Khách BALLBURN',
            'customer_email' => 'guest@example.test',
            'starts_at' => CarbonImmutable::parse("2026-11-03 {$startsAt}:00", $timezone)->utc(),
            'ends_at' => CarbonImmutable::parse("2026-11-03 {$endsAt}:00", $timezone)->utc(),
            'status' => $status,
            'payment_deadline_at' => $deadline,
            'base_amount' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'currency' => 'VND',
        ]);
    }

    private function courtPayload(): array
    {
        return [
            'name' => 'Sân Trung Tâm',
            'slug' => '',
            'description' => 'Sân thi đấu tiêu chuẩn.',
            'surface_type' => 'Outdoor',
            'court_type' => 'REGULAR',
            'capacity' => 4,
            'price_per_hour' => 150000,
            'currency' => 'VND',
            'is_active' => true,
            'sort_order' => 1,
            'operating_hours' => $this->weeklyHours(),
        ];
    }

    private function weeklyHours(): array
    {
        return collect(range(1, 7))->map(fn (int $day): array => [
            'day_of_week' => $day,
            'opens_at' => '05:00',
            'closes_at' => '23:00',
            'is_closed' => false,
        ])->all();
    }
}
