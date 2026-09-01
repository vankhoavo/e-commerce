<?php

namespace App\Http\Controllers\Settings;

use App\Models\EmailVerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmailChangeController
{
    public function request(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ])->validate();

        $request->session()->put('pending_email', $data['email']);
        $otp->send($request->user(), $data['email']);

        return to_route('email-change.edit')->with('status', 'email-change-code-sent');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('pending_email')) {
            return to_route('profile.edit');
        }

        return Inertia::render('settings/VerifyNewEmail', [
            'email' => $request->session()->get('pending_email'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get('pending_email');
        $data = Validator::make($request->all(), ['code' => ['required', 'digits:6']])->validate();
        $record = EmailVerificationCode::query()->where('user_id', $request->user()->id)->latest()->first();

        if ($email === '' || ! $record || $record->email !== $email || $record->expired()) {
            return back()->withErrors(['code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn.']);
        }
        if ($record->attempts >= 5) {
            return back()->withErrors(['code' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng gửi mã mới.']);
        }
        if (! hash_equals($record->code, $data['code'])) {
            $record->increment('attempts');
            return back()->withErrors(['code' => 'Mã xác thực không chính xác.']);
        }

        DB::transaction(function () use ($request, $record, $email): void {
            $record->update(['verified_at' => now()]);
            $request->user()->forceFill(['email' => $email, 'email_verified_at' => now()])->save();
        });

        $request->session()->forget('pending_email');
        return to_route('profile.edit')->with('status', 'email-changed');
    }
}
