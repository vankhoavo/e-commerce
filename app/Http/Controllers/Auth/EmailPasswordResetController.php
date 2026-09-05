<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Jobs\NotifyPasswordResetLockJob;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailPasswordResetController
{
    use PasswordValidationRules;

    private const MAX_ATTEMPTS = 3;

    public function showRequest(Request $request, PasswordResetOtpService $otp): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            if (! $user->is_active || blank($user->email)) {
                return to_route('profile.edit', ['section' => 'security'])->withErrors([
                    'password' => 'Tài khoản hiện tại không thể khôi phục mật khẩu.',
                ]);
            }

            $email = Str::lower(trim((string) $user->email));
            $key = 'password-reset:'.sha1($email.'|'.$request->ip());
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return to_route('profile.edit', ['section' => 'security'])->withErrors([
                    'password' => 'Bạn đã yêu cầu quá nhiều mã OTP. Vui lòng thử lại sau '.RateLimiter::availableIn($key).' giây.',
                ]);
            }

            RateLimiter::hit($key, 300);
            $this->clearSession($request);

            try {
                $code = $otp->send($user);
            } catch (\Throwable $exception) {
                report($exception);
                return to_route('profile.edit', ['section' => 'security'])->withErrors([
                    'password' => 'Không thể gửi mã OTP lúc này. Vui lòng kiểm tra cấu hình Email.',
                ]);
            }

            $this->storeResetSession($request, $user, $code->id);
            return to_route('security.password.verify')->with('status', 'Mã OTP đã được gửi đến email của bạn.');
        }

        return Inertia::render('auth/ForgotPassword', ['status' => session('status')]);
    }

    public function requestCode(Request $request, PasswordResetOtpService $otp): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $email = Str::lower(trim($validated['email']));
        $this->clearSession($request);

        $key = 'password-reset:'.sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Vui lòng thử lại sau '.RateLimiter::availableIn($key).' giây.']);
        }
        RateLimiter::hit($key, 300);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return back()->with('status', 'Nếu email tồn tại, mã OTP đã được gửi. Vui lòng kiểm tra hộp thư và thư mục Spam.');
        }

        try {
            $code = $otp->send($user);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['email' => 'Không thể gửi mã OTP lúc này. Vui lòng kiểm tra cấu hình Email.']);
        }

        $this->storeResetSession($request, $user, $code->id);
        return to_route('security.password.verify')->with('status', 'Mã OTP đã được gửi đến email của bạn.');
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code || $code->expired()) {
            $code?->delete();
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        return Inertia::render('auth/VerifyPasswordReset', [
            'email' => $this->maskEmail($code->email),
            'status' => session('status'),
            'expiresAt' => $code->expires_at->toISOString(),
            'remainingAttempts' => max(0, self::MAX_ATTEMPTS - $code->attempts),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code) {
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Phiên khôi phục đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        if ($code->expired()) {
            $code->delete();
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        if ($code->attempts >= self::MAX_ATTEMPTS) {
            return $this->lockAccount($request, $code);
        }

        if (! Hash::check($validated['code'], (string) $code->code_hash)) {
            $code->increment('attempts');
            $attempts = (int) $code->fresh()->attempts;
            if ($attempts >= self::MAX_ATTEMPTS) return $this->lockAccount($request, $code->fresh());
            return back()->withErrors(['code' => 'Mã OTP không chính xác. Bạn còn '.(self::MAX_ATTEMPTS - $attempts).' lần thử.']);
        }

        $code->forceFill(['verified_at' => now()])->save();
        $request->session()->put('password_reset_verified', true);
        return to_route('security.password.reset')->with('status', 'otp-verified');
    }

    public function resendCode(Request $request, PasswordResetOtpService $otp): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            $this->clearSession($request);
            return to_route('security.password.request');
        }

        try {
            $code = $otp->send($user);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['code' => 'Không thể gửi lại mã OTP lúc này. Vui lòng thử lại sau.']);
        }

        $this->storeResetSession($request, $user, $code->id);
        return back()->with('status', 'otp-resent')->with('otp_expires_at', $code->expires_at->toISOString());
    }

    public function showReset(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->get('password_reset_verified', false)) {
            return to_route('security.password.request')->withErrors(['email' => 'Bạn cần xác minh mã OTP trước khi đặt mật khẩu mới.']);
        }

        $code = $this->pendingCode($request);
        if (! $code || ! $code->verified_at || $code->expired()) {
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Phiên xác thực OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        if (! $this->pendingUser($request)) {
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Tài khoản khôi phục không còn khả dụng.']);
        }

        return Inertia::render('auth/ResetPasswordOtp', ['status' => session('status')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        if (! $request->session()->get('password_reset_verified', false)) {
            $this->clearSession($request);
            return to_route('security.password.request');
        }

        $user = $this->pendingUser($request);
        $code = $this->pendingCode($request);
        if (! $user || ! $code || ! $code->verified_at || $code->expired()) {
            $this->clearSession($request);
            return to_route('security.password.request')->withErrors(['email' => 'Phiên xác thực OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        $validated = $request->validate(['password' => $this->passwordRules()]);
        $user->forceFill(['password' => $validated['password']])->save();
        $code->update(['used_at' => now()]);
        $this->clearSession($request);

        return to_route('login')->with('status', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    private function storeResetSession(Request $request, User $user, int $codeId): void
    {
        $request->session()->put([
            'password_reset_user_id' => $user->id,
            'password_reset_email' => Str::lower(trim((string) $user->email)),
            'password_reset_code_id' => $codeId,
            'password_reset_verified' => false,
        ]);
    }

    private function pendingCode(Request $request): ?PasswordResetCode
    {
        $codeId = $request->session()->get('password_reset_code_id');
        $userId = $request->session()->get('password_reset_user_id');
        $email = Str::lower(trim((string) $request->session()->get('password_reset_email')));
        if (! $codeId || ! $userId || $email === '') return null;

        return PasswordResetCode::query()
            ->whereKey($codeId)
            ->where('user_id', $userId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('used_at')
            ->first();
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('password_reset_user_id');
        return $userId ? User::query()->whereKey($userId)->where('is_active', true)->first() : null;
    }

    private function lockAccount(Request $request, PasswordResetCode $code): RedirectResponse
    {
        $user = User::query()->whereKey($code->user_id)->first();
        if ($user) {
            $user->forceFill(['is_active' => false])->save();
            NotifyPasswordResetLockJob::dispatch(
                lockedUserId: $user->id,
                lockedUserName: (string) $user->name,
                lockedUserEmail: (string) $user->email,
            );
        }
        $code->delete();
        $this->clearSession($request);
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return to_route('login')->withErrors(['email' => 'Tài khoản đã bị khóa do nhập sai mã OTP 3 lần. Quản trị viên hoặc trợ lý quản trị viên đã được thông báo để phê duyệt mở khóa.']);
    }

    private function clearSession(Request $request): void
    {
        $request->session()->forget(['password_reset_user_id', 'password_reset_email', 'password_reset_code_id', 'password_reset_verified']);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') return $email;
        $visible = substr($local, 0, min(2, strlen($local)));
        return $visible.str_repeat('*', max(2, strlen($local) - strlen($visible))).'@'.$domain;
    }
}
