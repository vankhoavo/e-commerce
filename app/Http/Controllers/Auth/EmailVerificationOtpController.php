<?php

namespace App\Http\Controllers\Auth;

use App\Models\EmailVerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationOtpController
{
    private const MAX_ATTEMPTS = 5;
    private const MAX_SENDS = 5;
    private const SEND_COOLDOWN_SECONDS = 60;
    private const SEND_LOCKOUT_SECONDS = 3600;

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        if (! $user->is_active) {
            return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        if ($user->email_verified_at && ! $user->registration_pending) {
            return to_route('profile.edit');
        }

        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('email', $user->email)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $record) {
            return to_route('register')->withErrors([
                'email' => 'Không tìm thấy mã xác thực đang chờ. Vui lòng thực hiện đăng ký lại.',
            ]);
        }

        return Inertia::render('auth/VerifyEmailOtp', [
            'email' => $user->email,
            'expiresAt' => $record->expires_at?->toISOString(),
            'hasPendingCode' => ! $record->expired(),
            'remainingAttempts' => max(0, self::MAX_ATTEMPTS - $record->attempts),
            'registrationPending' => (bool) $user->registration_pending,
            'resendAvailableIn' => $this->resendAvailableIn($user->email, $request->ip(), $record),
            'resendLocked' => $this->resendLocked($user->email, $request->ip()),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        if (! $user->is_active) {
            return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        if ($user->email_verified_at && ! $user->registration_pending) {
            return to_route('profile.edit');
        }

        $data = Validator::make($request->all(), [
            'code' => ['required', 'digits:6'],
        ])->validate();

        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('email', $user->email)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $record || $record->expired()) {
            return back()->withErrors(['code' => 'Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng chờ mã hết hạn rồi gửi mã mới.']);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return $this->lockUser($user, $record);
        }

        if (! Hash::check($data['code'], $record->code)) {
            $record->increment('attempts');
            $attempts = (int) $record->fresh()->attempts;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->lockUser($user, $record);
                return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa do nhập sai mã OTP quá 5 lần.']);
            }

            return back()->withErrors(['code' => "Mã xác thực không chính xác. Bạn còn ".(self::MAX_ATTEMPTS - $attempts)." lần thử."]);
        }

        DB::transaction(function () use ($record, $user): void {
            $record->update(['verified_at' => now()]);
            $user->forceFill([
                'email_verified_at' => now(),
                'registration_pending' => false,
            ])->save();
        });

        return to_route('profile.edit')->with('status', 'email-verified');
    }

    public function resend(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return to_route('login');
        }

        if (! $user->is_active) {
            return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        if ($user->email_verified_at && ! $user->registration_pending) {
            return to_route('profile.edit');
        }

        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('email', $user->email)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($record && ! $record->expired()) {
            $remaining = max(1, now()->diffInSeconds($record->expires_at, false));
            return back()->withErrors(['code' => "Mã OTP hiện tại chưa hết hạn. Vui lòng chờ {$remaining} giây rồi mới yêu cầu mã mới."]);
        }

        $key = $this->sendKey($user->email, $request->ip());
        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS)) {
            $remaining = RateLimiter::availableIn($key);
            return back()->withErrors(['code' => 'Bạn đã yêu cầu gửi lại OTP 5 lần. Vui lòng quay lại sau '.ceil($remaining / 60).' phút.']);
        }

        try {
            $code = $otp->send($user, $user->email);
            RateLimiter::hit($key, self::SEND_LOCKOUT_SECONDS);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['code' => 'Không thể gửi mã OTP lúc này. Vui lòng kiểm tra cấu hình Email và thử lại sau.']);
        }

        return back()
            ->with('status', 'verification-code-sent')
            ->with('otp_expires_at', $code->expires_at->toISOString());
    }

    public function defer(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return to_route('login');
        }

        if ($user->registration_pending) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->withErrors([
                'email' => 'Bạn cần xác thực Email trước khi sử dụng tài khoản.',
            ]);
        }

        if (! $user->is_active) {
            return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }

        if ($user->email_verified_at) {
            return to_route('profile.edit');
        }

        return to_route('profile.edit')->with('status', 'email-verification-deferred');
    }

    private function lockUser($user, ?EmailVerificationCode $record = null): RedirectResponse
    {
        DB::transaction(function () use ($user, $record): void {
            $user->forceFill(['is_active' => false])->save();
            if ($record) {
                $record->delete();
            }
        });

        return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa do nhập sai mã OTP quá 5 lần. Quản trị viên hoặc trợ lý quản trị viên đã được thông báo để phê duyệt mở khóa.']);
    }

    private function sendKey(string $email, string $ip): string
    {
        return 'otp-send:email-verification:'.sha1(Str::lower(trim($email)).'|'.$ip);
    }

    private function resendAvailableIn(string $email, string $ip, ?EmailVerificationCode $record): int
    {
        if ($record && ! $record->expired()) {
            return max(0, now()->diffInSeconds($record->expires_at, false));
        }

        return RateLimiter::availableIn($this->sendKey($email, $ip));
    }

    private function resendLocked(string $email, string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->sendKey($email, $ip), self::MAX_SENDS);
    }
}
