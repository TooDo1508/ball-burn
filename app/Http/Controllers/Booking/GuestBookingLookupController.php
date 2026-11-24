<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\LookupGuestBookingRequest;
use App\Http\Resources\GuestBookingLookupResource;
use App\Services\Booking\GuestBookingService;
use App\Support\SeoUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GuestBookingLookupController extends Controller
{
    public function index(): Response
    {
        $description = 'Tra cứu trạng thái đặt sân và thanh toán tại BALLBURN PickleClub & Bistro.';

        return response()->view('welcome', ['seo' => [
            'title' => 'Tra cứu đơn hàng | BALLBURN PickleClub & Bistro',
            'description' => $description,
            'canonical' => SeoUrl::to('/booking/lookup'),
            'image' => SeoUrl::to('/images/pickleball/court-rally-wide.jpg'),
            'structured_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Tra cứu đơn hàng BALLBURN', 'description' => $description],
        ]])->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, follow');
    }

    public function lookup(LookupGuestBookingRequest $request, GuestBookingService $service): JsonResponse
    {
        $order = $service->lookup($request->validated('booking_code'), $request->validated('customer_phone'));
        if (! $order) {
            return response()->json(['success' => false, 'message' => config('api.error_message')], 404)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json(['success' => true, 'data' => (new GuestBookingLookupResource($order))->resolve($request)])
            ->header('Cache-Control', 'no-store, private');
    }
}
