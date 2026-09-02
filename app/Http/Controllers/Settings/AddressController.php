<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->forceFill(['address' => $data['address'] ?? null])->save();

        return to_route('profile.edit', ['section' => 'profile'])->with('status', 'address-updated');
    }
}
