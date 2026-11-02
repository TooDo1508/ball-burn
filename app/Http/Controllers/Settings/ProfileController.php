<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\Settings\AccountSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly AccountSettingsService $settings) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', $this->settings->profileData(
            $request->user(),
            $request->session()->get('status'),
        ));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->settings->updateProfile($request->user(), $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }
}
