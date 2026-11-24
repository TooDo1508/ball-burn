<?php

namespace Tests\Unit;

use App\Models\BookingOrder;
use App\Repositories\Contracts\BookingRepository;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Booking\GuestBookingService;
use App\Services\Court\CourtPricingService;
use Mockery;
use Tests\TestCase;

class GuestBookingLookupTest extends TestCase
{
    public function test_lookup_requires_the_phone_attached_to_the_order(): void
    {
        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('findGuestOrderByCode')->twice()->with('BB-20261125-ABC123')
            ->andReturn(new BookingOrder(['customer_phone' => '0886 688 614']));
        $service = new GuestBookingService(
            Mockery::mock(CourtRepository::class),
            $repository,
            Mockery::mock(CourtPricingService::class),
        );

        $this->assertInstanceOf(BookingOrder::class, $service->lookup('BB-20261125-ABC123', '0886688614'));
        $this->assertNull($service->lookup('BB-20261125-ABC123', '0900000000'));
    }
}
