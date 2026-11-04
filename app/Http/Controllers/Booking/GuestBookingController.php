<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\GetBookingAvailabilityRequest;
use App\Http\Requests\Booking\StoreGuestBookingRequest;
use App\Services\Booking\GuestBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GuestBookingController extends Controller
{
    public function __construct(private readonly GuestBookingService $bookings) {}

    public function indexPage(): View
    {
        return view('welcome', ['seo' => $this->bookingSeo()]);
    }

    public function statusPage(string $token): Response
    {
        return response()->view('welcome', ['seo' => $this->bookingSeo()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function availability(GetBookingAvailabilityRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bookings->availability(
                $request->validated('date'),
                $request->integer('court_id') ?: null,
            ),
        ]);
    }

    public function store(StoreGuestBookingRequest $request): JsonResponse
    {
        $result = $this->bookings->create($request->validated());
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $bookings = [];
        foreach ($result['bookings'] as $index => $booking) {
            $token = $result['status_tokens'][$index];
            $bookings[] = [
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'court' => $booking->court->name,
                'date' => $booking->starts_at->timezone($timezone)->format('Y-m-d'),
                'starts_at' => $booking->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'status_url' => url('/booking/status/'.$token),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Đặt sân thành công.',
            'data' => ['bookings' => $bookings],
        ], 201);
    }

    public function status(string $token): JsonResponse
    {
        $status = $this->bookings->status($token);
        abort_if($status === null, 404);

        return response()->json(['success' => true, 'data' => $status])
            ->header('Cache-Control', 'no-store, private');
    }

    /** @return array<string, string> */
    private function bookingSeo(): array
    {
        $description = 'Chọn sân, giờ chơi và đặt lịch pickleball tại BALLBURN PickleClub & Bistro.';

        return [
            'title' => 'Đặt sân | BALLBURN PickleClub & Bistro',
            'description' => $description,
            'canonical' => url('/booking'),
            'image' => url('/images/pickleball/court-rally-wide.jpg'),
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'SportsActivityLocation',
                'name' => 'BALLBURN PickleClub & Bistro',
                'description' => $description,
                'url' => url('/booking'),
                'image' => url('/images/pickleball/court-rally-wide.jpg'),
            ],
        ];
    }
}
