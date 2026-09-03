<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerActivationController
{
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::CUSTOMER, 404);
        abort_unless(filled($user->google_id), 422, 'Tài khoản này không đăng nhập bằng Google.');

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update(['is_active' => $data['is_active']]);

        return back()->with('success', $data['is_active'] ? 'Đã kích hoạt tài khoản Google.' : 'Đã khóa tài khoản Google.');
    }
}
