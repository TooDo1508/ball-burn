<?php

namespace App\Http\Controllers\Admin\Court;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtClosureRequest;
use App\Models\Court;
use App\Services\Admin\Court\CourtManagementService;
use Illuminate\Http\RedirectResponse;

class CourtClosureController extends Controller
{
    public function __construct(private readonly CourtManagementService $courts) {}

    public function store(StoreCourtClosureRequest $request): RedirectResponse
    {
        $court = $this->courts->find((int) $request->validated('court_id'));
        $this->authorize('update', $court);
        $this->courts->createClosure($request->validated(), (int) $request->user()->id);

        return back()->with('success', 'Đã khóa lịch sân.');
    }

    public function destroy(int $closureId): RedirectResponse
    {
        $this->authorize('viewAny', Court::class);
        $this->courts->deleteClosure($closureId);

        return back()->with('success', 'Đã mở lại lịch sân.');
    }
}
