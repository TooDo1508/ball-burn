<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminBookingRequest;
use App\Models\Booking;
use App\Services\Admin\Booking\AdminBookingService;
use Illuminate\Http\RedirectResponse;

class AdminBookingController extends Controller
{
    public function __construct(private readonly AdminBookingService $bookings) {}

    public function store(StoreAdminBookingRequest $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);
        $booking = $this->bookings->create($request->validated(), (int) $request->user()->id);

        return back()->with('success', "Đã tạo booking {$booking->booking_code}.");
    }
}
