<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateHomePageRequest;
use App\Services\Settings\HomePageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomePageController extends Controller
{
    public function __construct(private readonly HomePageService $homePage) {}

    public function edit(): Response
    {
        return Inertia::render('settings/HomePage', ['content' => $this->homePage->content()]);
    }

    public function update(UpdateHomePageRequest $request): RedirectResponse
    {
        $this->homePage->update($request->validated('content'), (int) $request->user()->id);

        return back()->with('success', 'Trang ch\u1ee7 đã được cập nh\u1eadt.');
    }
}
