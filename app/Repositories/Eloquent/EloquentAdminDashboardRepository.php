<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Repositories\Contracts\AdminDashboardRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class EloquentAdminDashboardRepository implements AdminDashboardRepository
{
    public function snapshot(
        CarbonImmutable $fromUtc,
        CarbonImmutable $toUtc,
        CarbonImmutable $previousFromUtc,
        CarbonImmutable $previousToUtc,
        CarbonImmutable $nowUtc,
    ): array {
        return [
            'status_counts' => $this->statusCounts($fromUtc, $toUtc),
            'previous_status_counts' => $this->statusCounts($previousFromUtc, $previousToUtc),
            'revenue_rows' => $this->revenueRows($fromUtc, $toUtc),
            'previous_revenue_rows' => $this->revenueRows($previousFromUtc, $previousToUtc),
            'booked_starts' => Booking::query()
                ->occupyingSchedule()
                ->where('starts_at', '>=', $fromUtc)
                ->where('starts_at', '<', $toUtc)
                ->get(['starts_at']),
            'upcoming_bookings' => Booking::query()
                ->occupyingSchedule()
                ->where('starts_at', '>=', $nowUtc)
                ->with([
                    'court:id,name',
                    'order:id,booking_code,status,payment_deadline_at,total_amount,currency',
                ])
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
        ];
    }

    /** @return array<string, int> */
    private function statusCounts(CarbonImmutable $fromUtc, CarbonImmutable $toUtc): array
    {
        return BookingOrder::query()
            ->whereHas('bookings')
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toUtc)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn (BookingOrder $order): array => [$order->status => (int) $order->aggregate])
            ->all();
    }

    /** @return Collection<int, BookingOrder> */
    private function revenueRows(CarbonImmutable $fromUtc, CarbonImmutable $toUtc): Collection
    {
        return BookingOrder::query()
            ->whereIn('status', ['PAID', 'CANCELLED'])
            ->whereNotNull('paid_at')
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toUtc)
            ->get(['created_at', 'total_amount', 'currency']);
    }
}
