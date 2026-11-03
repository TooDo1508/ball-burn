<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateContactSettingsRequest;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactSettingsController extends Controller
{
    public function __construct(private readonly ContactSettingsService $settings) {}

    public function edit(): Response
    {
        return Inertia::render('settings/Contact', ['contact' => $this->settings->publicSettings()]);
    }

    public function update(UpdateContactSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated('contact'), (int) $request->user()->id);

        return back()->with('success', 'Đã lưu thông tin liên hệ của website.');
    }
}
