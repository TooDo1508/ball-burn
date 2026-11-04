<?php

namespace App\Http\Controllers\Admin\Court;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexCourtScheduleRequest;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Models\Court;
use App\Services\Admin\Court\CourtManagementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourtAdminController extends Controller
{
    public function __construct(private readonly CourtManagementService $courts) {}

    public function index(IndexCourtScheduleRequest $request): Response
    {
        $this->authorize('viewAny', Court::class);
        $date = $request->validated('date')
            ?? CarbonImmutable::now((string) config('venue.timezone', 'Asia/Ho_Chi_Minh'))->format('Y-m-d');

        return Inertia::render('admin/courts/Index', $this->courts->schedule($date));
    }

    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $this->authorize('create', Court::class);
        $court = $this->courts->create($request->validated(), $request->file('image'));

        return back()->with('success', "Đã tạo {$court->name}.");
    }

    public function update(StoreCourtRequest $request, int $courtId): RedirectResponse
    {
        $court = $this->courts->find($courtId);
        $this->authorize('update', $court);
        $this->courts->update($court, $request->validated(), $request->file('image'));

        return back()->with('success', "Đã cập nhật {$court->name}.");
    }
}
