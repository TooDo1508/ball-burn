<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\GetBookingAvailabilityRequest;
use App\Http\Requests\Booking\StoreGuestPaymentProofRequest;
use App\Http\Requests\Booking\StoreGuestBookingRequest;
use App\Services\Booking\GuestBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
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
                $request->query('hold_token'),
            ),
        ]);
    }

    public function syncHolds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'hold_token' => ['nullable', 'regex:/^[A-Za-z0-9]{64}$/'],
            'slots' => ['required', 'array', 'max:64'],
            'slots.*.court_id' => ['required', 'integer', 'exists:courts,id'],
            'slots.*.starts_at' => ['required', 'date_format:H:i'],
            'slots.*.ends_at' => ['required', 'date_format:H:i'],
        ]);
        foreach ($validated['slots'] as $index => $slot) {
            if (! in_array(substr($slot['starts_at'], -2), ['00', '30'], true) || ! in_array(substr($slot['ends_at'], -2), ['00', '30'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(["slots.{$index}" => 'Khung giữ chỗ phải bắt đầu và kết thúc theo mốc 30 phút.']);
            }
        }
        return response()->json(['success' => true, 'data' => $this->bookings->syncHolds($validated['date'], $validated['slots'], $validated['hold_token'] ?? null)]);
    }

    public function releaseHolds(Request $request): JsonResponse
    {
        $validated = $request->validate(['hold_token' => ['required', 'regex:/^[A-Za-z0-9]{64}$/']]);
        $this->bookings->releaseHolds($validated['hold_token']);
        return response()->json(['success' => true]);
    }

    public function paymentInstructions(StoreGuestBookingRequest $request): JsonResponse
    {
        $instructions = $this->bookings->paymentInstructions($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Lượt giữ sân và thông tin thanh toán đã sẵn sàng. Đơn đặt sân sẽ được tạo khi bạn gửi ảnh chuyển khoản.',
            'data' => [
                'payment_reference' => $instructions['payment_reference'],
                'payment_deadline_at' => $instructions['payment_deadline_at']->toISOString(),
                'total_amount' => $instructions['total_amount'],
                'currency' => $instructions['currency'],
            ],
        ]);
    }

    public function submitPaymentProof(StoreGuestPaymentProofRequest $request): JsonResponse
    {
        $result = $this->bookings->createWithPaymentProof($request->validated());
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $bookings = [];

        foreach ($result['order']->bookings as $booking) {
            $bookings[] = [
                'status' => $result['order']->status,
                'court' => $booking->court->name,
                'date' => $booking->starts_at->timezone($timezone)->format('Y-m-d'),
                'starts_at' => $booking->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi một ảnh chuyển khoản cho đơn. Đơn đang chờ nhân viên xác nhận thanh toán.',
            'data' => [
                'booking_code' => $result['order']->booking_code,
                'bookings' => $bookings,
                'total_amount' => $result['order']->total_amount,
                'currency' => $result['order']->currency,
                'status' => $result['order']->currentStatus(),
                'payment_deadline_at' => $result['order']->payment_deadline_at?->toISOString(),
                'status_url' => url('/booking/status/'.$request->validated('status_token')),
                'payment_submitted' => true,
            ],
        ], 201);
    }

    public function status(string $token): JsonResponse
    {
        $status = $this->bookings->status($token);
        abort_if($status === null, 404);
        $status['status_url'] = url('/booking/status/'.$token);

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
