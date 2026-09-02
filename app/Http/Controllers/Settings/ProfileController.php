<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class ProfileController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'twoFactorEnabled' => Features::canManageTwoFactorAuthentication() ? $user->hasEnabledTwoFactorAuthentication() : false,
            'requiresConfirmation' => Features::canManageTwoFactorAuthentication() ? Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm') : false,
            'passkeys' => Features::canManagePasskeys() ? $user->passkeys()->select(['id','name','credential','created_at','last_used_at'])->latest()->get()->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])->values()->all() : [],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['email']);

        $province = trim((string) ($data['address_province'] ?? ''));
        $ward = trim((string) ($data['address_ward'] ?? ''));
        $detail = trim((string) ($data['address_detail'] ?? ''));
        $parts = array_values(array_filter([$detail, $ward, $province], static fn (string $value): bool => $value !== ''));
        $data['address'] = $parts ? implode(', ', $parts) : null;

        $request->user()->fill($data)->save();

        return to_route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
