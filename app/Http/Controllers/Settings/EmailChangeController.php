<?php

namespace App\Http\Controllers\Settings;

use App\Models\EmailVerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmailChangeController
{
    private const MAX_ATTEMPTS = 5;

    public function request(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ])->validate();

        $email = mb_strtolower(trim($data['email']));
        $request->session()->put('pending_email', $email);

        try {
            $otp->send($request->user(), $email);
        } catch (\Throwable $exception) {
            report($exception);
            $request->session()->forget('pending_email');

            return back()->withErrors([
                'email' => 'Không thể gửi mã xác thực lúc này. Vui lòng kiểm tra cấu hình email và thử lại sau.',
            ]);
        }

        return to_route('email-change.edit')->with('status', 'email-change-code-sent');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $email = (string) $request->session()->get('pending_email');
        if ($email === '') {
            return to_route('profile.edit');
        }

        $record = $this->pendingRecord($request, $email);
        if (! $record || $record->expired()) {
            return to_route('profile.edit')->withErrors([
                'email' => 'Mã xác thực đã hết hạn. Vui lòng gửi mã mới.',
            ]);
        }

        return Inertia::render('settings/VerifyNewEmail', [
            'email' => $email,
            'expiresAt' => $record->expires_at->toISOString(),
            'remainingAttempts' => max(0, self::MAX_ATTEMPTS - $record->attempts),
        ]);
    }

    public function resend(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $email = (string) $request->session()->get('pending_email');
        if ($email === '') {
            return to_route('profile.edit');
        }

        try {
            $record = $otp->send($request->user(), $email);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'code' => 'Không thể gửi lại mã xác thực lúc này. Vui lòng thử lại sau.',
            ]);
        }

        return back()
            ->with('status', 'email-change-code-sent')
            ->with('otp_expires_at', $record->expires_at->toISOString());
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        $email = mb_strtolower(trim((string) $request->session()->get('pending_email')));

        if ($email === '') {
            return to_route('profile.edit')->withErrors([
                'code' => 'Phiên xác thực đã hết hạn. Vui lòng gửi mã mới.',
            ]);
        }

        $data = Validator::make($request->all(), [
            'code' => ['required', 'digits:6'],
        ])->validate();

        $record = $this->pendingRecord($request, $email);
        if (! $record || $record->expired()) {
            return back()->withErrors([
                'code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng gửi mã mới.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->delete();

            return back()->withErrors([
                'code' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng gửi mã mới.',
            ]);
        }

        if (! Hash::check($data['code'], $record->code)) {
            $record->increment('attempts');
            $attempts = $record->fresh()->attempts;

            return back()->withErrors([
                'code' => 'Mã xác thực không chính xác. Bạn còn '.max(0, self::MAX_ATTEMPTS - $attempts).' lần thử.',
            ]);
        }

        DB::transaction(function () use ($user, $record, $email): void {
            $record->update(['verified_at' => now()]);
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => now(),
                'avatar' => '/images/techstore-logo.svg',
            ])->save();
        });

        $request->session()->forget('pending_email');

        return to_route('profile.edit')->with('status', 'email-changed');
    }

    private function pendingRecord(Request $request, string $email): ?EmailVerificationCode
    {
        return EmailVerificationCode::query()
            ->where('user_id', $request->user()->id)
            ->where('email', $email)
            ->whereNull('verified_at')
            ->latest()
            ->first();
    }
}
