<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailPasswordResetController
{
    use PasswordValidationRules;

    public function showRequest(): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function requestCode(Request $request, PasswordResetOtpService $otp): RedirectResponse
    {
        $email = Str::lower(trim($request->string('email')->toString()));
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $key = 'password-reset:'.sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Vui lòng thử lại sau {$seconds} giây."]);
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

        $request->session()->put([
            'password_reset_user_id' => $user->id,
            'password_reset_code_id' => $code->id,
            'password_reset_verified' => false,
        ]);

        return to_route('password.email.verify');
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code) {
            return to_route('password.email.request');
        }

        return Inertia::render('auth/VerifyPasswordReset', [
            'email' => $this->maskEmail($code->email),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code) {
            return to_route('password.email.request')->withErrors(['email' => 'Phiên khôi phục đã hết hạn.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($code->expired()) {
            $code->delete();
            return to_route('password.email.request')->withErrors(['email' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        if ($code->attempts >= 5) {
            $code->delete();
            return to_route('password.email.request')->withErrors(['email' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng yêu cầu mã mới.']);
        }

        if (! Hash::check($validated['code'], $code->code_hash)) {
            $code->increment('attempts');
            return back()->withErrors(['code' => 'Mã OTP không chính xác.']);
        }

        $code->update(['used_at' => now()]);
        $request->session()->put('password_reset_verified', true);

        return to_route('password.email.reset');
    }

    public function showReset(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->get('password_reset_verified')) {
            return to_route('password.email.request');
        }

        if (! $this->pendingUser($request)) {
            $this->clearSession($request);
            return to_route('password.email.request');
        }

        return Inertia::render('auth/ResetPasswordOtp');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $request->session()->get('password_reset_verified') || ! $user) {
            $this->clearSession($request);
            return to_route('password.email.request');
        }

        $validated = $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        $this->clearSession($request);

        return to_route('login')->with('status', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    private function pendingCode(Request $request): ?PasswordResetCode
    {
        $codeId = $request->session()->get('password_reset_code_id');
        $userId = $request->session()->get('password_reset_user_id');

        if (! $codeId || ! $userId) {
            return null;
        }

        return PasswordResetCode::query()
            ->whereKey($codeId)
            ->where('user_id', $userId)
            ->first();
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('password_reset_user_id');
        return $userId ? User::query()->whereKey($userId)->where('is_active', true)->first() : null;
    }

    private function clearSession(Request $request): void
    {
        $request->session()->forget([
            'password_reset_user_id',
            'password_reset_code_id',
            'password_reset_verified',
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return $email;
        }

        $visible = substr($local, 0, min(2, strlen($local)));
        return $visible.str_repeat('*', max(2, strlen($local) - strlen($visible))).'@'.$domain;
    }
}
