<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAdminDashboardRequest;
use App\Services\Admin\AdminDashboardService;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(IndexAdminDashboardRequest $request, AdminDashboardService $dashboard): Response
    {
        $filters = $request->validated();

        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $range = $filters['range'] ?? '7';

        if ($range === 'custom' && ! empty($filters['from']) && ! empty($filters['to'])) {
            $from = CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], $timezone);
            $lastDay = CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], $timezone);
        } else {
            $days = match ($range) {
                'today' => 1,
                '30' => 30,
                default => 7,
            };
            $from = $today->subDays($days - 1);
            $lastDay = $today;
        }

        $to = $lastDay->addDay();
        $periodDays = max(1, (int) $from->diffInDays($to));
        $previousFrom = $from->subDays($periodDays);

        return Inertia::render('Dashboard', [
            'period' => [
                'range' => $range,
                'from' => $from->format('Y-m-d'),
                'to' => $lastDay->format('Y-m-d'),
                'label' => $from->format('d/m/Y').' – '.$lastDay->format('d/m/Y'),
                'previous_label' => $previousFrom->format('d/m/Y').' – '.$from->subDay()->format('d/m/Y'),
            ],
            'overview' => $dashboard->overview($from, $to, $previousFrom, $from, $timezone),
        ]);
    }
}
