<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Repositories\Contracts\AdminDashboardRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function __construct(private readonly AdminDashboardRepository $dashboard) {}

    /** @return array<string, mixed> */
    public function overview(
        CarbonImmutable $fromLocal,
        CarbonImmutable $toLocal,
        CarbonImmutable $previousFromLocal,
        CarbonImmutable $previousToLocal,
        string $timezone,
    ): array {
        $nowUtc = CarbonImmutable::now('UTC');
        $snapshot = $this->dashboard->snapshot(
            $fromLocal->utc(),
            $toLocal->utc(),
            $previousFromLocal->utc(),
            $previousToLocal->utc(),
            $nowUtc,
        );

        $statuses = $this->completeStatuses($snapshot['status_counts']);
        $previousStatuses = $this->completeStatuses($snapshot['previous_status_counts']);
        $revenue = $this->sumRevenue($snapshot['revenue_rows'], $timezone);
        $previousRevenue = $this->sumRevenue($snapshot['previous_revenue_rows'], $timezone);
        $chartCurrency = isset($revenue['totals']['VND'])
            ? 'VND'
            : (array_key_first($revenue['totals']) ?? 'VND');

        return [
            'metrics' => [
                'total' => array_sum($statuses),
                'paid' => $statuses['PAID'],
                'pending' => $statuses['PENDING_PAYMENT'],
                'cancelled' => $statuses['CANCELLED'],
                'previous' => [
                    'total' => array_sum($previousStatuses),
                    'paid' => $previousStatuses['PAID'],
                    'pending' => $previousStatuses['PENDING_PAYMENT'],
                    'cancelled' => $previousStatuses['CANCELLED'],
                ],
            ],
            'revenue' => [
                'totals' => $this->formatMoneyMap($revenue['totals']),
                'previous_totals' => $this->formatMoneyMap($previousRevenue['totals']),
                'chart_currency' => $chartCurrency,
                'chart_total' => $this->formatMinorUnits($revenue['totals'][$chartCurrency] ?? 0),
                'chart_previous_total' => $this->formatMinorUnits($previousRevenue['totals'][$chartCurrency] ?? 0),
                'series' => $this->revenueSeries($fromLocal, $toLocal, $revenue['daily'], $chartCurrency),
            ],
            'popular_hours' => $this->popularHours($snapshot['booked_starts'], $timezone),
            'upcoming_bookings' => $this->upcomingBookings($snapshot['upcoming_bookings'], $timezone),
        ];
    }

    /** @param array<string, int> $counts
     *  @return array<string, int>
     */
    private function completeStatuses(array $counts): array
    {
        return [
            'PAID' => $counts['PAID'] ?? 0,
            'PENDING_PAYMENT' => $counts['PENDING_PAYMENT'] ?? 0,
            'CANCELLED' => $counts['CANCELLED'] ?? 0,
        ];
    }

    /** @param Collection<int, Booking> $bookings
     *  @return array<int, array{hour: string, count: int}>
     */
    private function popularHours(Collection $bookings, string $timezone): array
    {
        $counts = [];
        foreach ($bookings as $booking) {
            $hour = $booking->starts_at->timezone($timezone)->format('H');
            $counts[$hour] = ($counts[$hour] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 9, true);
        ksort($counts);

        return collect($counts)->map(fn (int $count, string $hour): array => [
            'hour' => $hour.':00',
            'count' => $count,
        ])->values()->all();
    }

    /** @param Collection<int, Booking> $bookings
     *  @return array<int, array<string, mixed>>
     */
    private function upcomingBookings(Collection $bookings, string $timezone): array
    {
        return $bookings->map(fn (Booking $booking): array => [
            'id' => $booking->id,
            'booking_code' => $booking->order?->booking_code ?? $booking->booking_code,
            'court' => $booking->court?->name ?? 'Sân',
            'customer_name' => $booking->customer_name,
            'starts_at' => $booking->starts_at->timezone($timezone)->format('d/m H:i'),
            'status' => $booking->order?->status ?? 'PENDING_PAYMENT',
        ])->values()->all();
    }

    /** @param Collection<int, \App\Models\BookingOrder> $rows
     *  @return array{totals: array<string, int>, daily: array<string, array<string, int>>}
     */
    private function sumRevenue(Collection $rows, string $timezone): array
    {
        $totals = [];
        $daily = [];

        foreach ($rows as $row) {
            $amount = $this->toMinorUnits((string) $row->total_amount);
            $currency = (string) $row->currency;
            $day = $row->created_at->timezone($timezone)->format('Y-m-d');
            $totals[$currency] = ($totals[$currency] ?? 0) + $amount;
            $daily[$day][$currency] = ($daily[$day][$currency] ?? 0) + $amount;
        }

        return ['totals' => $totals, 'daily' => $daily];
    }

    /** @param array<string, int> $totals
     *  @return array<int, array{currency: string, amount: string}>
     */
    private function formatMoneyMap(array $totals): array
    {
        $formatted = [];
        foreach ($totals as $currency => $amount) {
            $formatted[] = ['currency' => $currency, 'amount' => $this->formatMinorUnits($amount)];
        }

        return $formatted;
    }

    /** @param array<string, array<string, int>> $daily
     *  @return array<int, array{date: string, label: string, amount: string}>
     */
    private function revenueSeries(CarbonImmutable $from, CarbonImmutable $to, array $daily, string $currency): array
    {
        $series = [];
        for ($date = $from->startOfDay(); $date->lt($to); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'label' => $date->format('d/m'),
                'amount' => $this->formatMinorUnits($daily[$key][$currency] ?? 0),
            ];
        }

        return $series;
    }

    private function toMinorUnits(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $minor = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$minor : $minor;
    }

    private function formatMinorUnits(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $amount = abs($amount);

        return $sign.intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
