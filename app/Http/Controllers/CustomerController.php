<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Customers', [
            'users' => User::query()
                ->where('role', UserRole::CUSTOMER->value)
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::CUSTOMER, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_province' => ['nullable', 'string', 'max:120'],
            'address_ward' => ['nullable', 'string', 'max:160'],
            'address_detail' => ['nullable', 'string', 'max:250'],
        ]);

        $data['address'] = collect([
            $data['address_detail'] ?? null,
            $data['address_ward'] ?? null,
            $data['address_province'] ?? null,
        ])->filter()->implode(', ');

        $user->update($data);

        return back()->with('success', 'Đã cập nhật thông tin khách hàng.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::CUSTOMER, 404);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Đã kích hoạt tài khoản khách hàng.' : 'Đã khóa tài khoản khách hàng.');
    }

    public function verify(User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::CUSTOMER, 404);
        $user->update([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Đã xác thực và kích hoạt tài khoản khách hàng.');
    }
}
