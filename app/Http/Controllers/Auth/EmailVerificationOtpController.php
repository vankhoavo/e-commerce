<?php

namespace App\Http\Controllers\Auth;

use App\Models\EmailVerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationOtpController
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return to_route('login');
        }

        if ($request->user()->email_verified_at) {
            return to_route('profile.edit');
        }

        $record = EmailVerificationCode::query()
            ->where('user_id', $request->user()->id)
            ->where('email', $request->user()->email)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        return Inertia::render('auth/VerifyEmailOtp', [
            'email' => $request->user()->email,
            'expiresAt' => $record?->expires_at?->toISOString(),
            'hasPendingCode' => (bool) ($record && ! $record->expired()),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), ['code' => ['required', 'digits:6']])->validate();
        $record = EmailVerificationCode::query()
            ->where('user_id', $request->user()->id)
            ->where('email', $request->user()->email)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $record || $record->expired()) {
            return back()->withErrors(['code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng gửi mã mới.']);
        }

        if ($record->attempts >= 5) {
            return back()->withErrors(['code' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng gửi mã mới.']);
        }

        if (! Hash::check($data['code'], $record->code)) {
            $record->increment('attempts');
            return back()->withErrors(['code' => 'Mã xác thực không chính xác.']);
        }

        DB::transaction(function () use ($record, $request): void {
            $record->update(['verified_at' => now()]);
            $request->user()->forceFill(['email_verified_at' => now()])->save();
        });

        return to_route('profile.edit')->with('status', 'email-verified');
    }

    public function resend(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return to_route('profile.edit');
        }

        try {
            $record = $otp->send($user, $user->email);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['code' => 'Không thể gửi mã OTP lúc này. Vui lòng thử lại sau.']);
        }

        return back()->with('status', 'verification-code-sent')->with('otp_expires_at', $record->expires_at->toISOString());
    }

    public function defer(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return to_route('login');
        }

        if ($request->user()->email_verified_at) {
            return to_route('profile.edit');
        }

        return to_route('profile.edit')->with('status', 'email-verification-deferred');
    }
}
