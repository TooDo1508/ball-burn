<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Services\Settings\AccountSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __construct(private readonly AccountSettingsService $settings) {}

    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();
        $props = $this->settings->securityData($request->user());
        $props['passwordRules'] = Password::defaults()->toPasswordRulesString();

        return Inertia::render('settings/Security', $props);
    }

    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $this->settings->updatePassword($request->user(), $request->validated('password'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
