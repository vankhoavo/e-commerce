<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorSecurityController extends Controller
{
    use PasswordValidationRules;

    public function showPasswordReset(Request $request, PasswordResetOtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_active || blank($user->email)) {
            return back()->withErrors(['password' => 'Tài khoản admin hiện tại không thể khôi phục mật khẩu.']);
        }

        try {
            $code = $otp->send($user);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['password' => 'Không thể gửi mã OTP lúc này. Vui lòng kiểm tra cấu hình Email.']);
        }

        $request->session()->put([
            'admin_security_user_id' => $user->id,
            'admin_security_code_id' => $code->id,
            'admin_security_verified' => false,
        ]);

        return to_route('admin.security.password.verify')->with('status', 'Mã OTP đã được gửi đến email của tài khoản admin.');
    }

    public function showPasswordVerify(Request $request): Response|RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code || $code->expired()) {
            $this->clearPasswordSession($request);

            return to_route('admin.administrators')->withErrors(['password' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu lại.']);
        }

        return Inertia::render('admin/VerifyAdministratorPasswordReset', [
            'email' => $this->maskEmail((string) $code->email),
            'expiresAt' => $code->expires_at->toISOString(),
            'remainingAttempts' => max(0, 3 - $code->attempts),
            'status' => session('status'),
        ]);
    }

    public function verifyPassword(Request $request): RedirectResponse
    {
        $code = $this->pendingCode($request);
        if (! $code || $code->expired()) {
            $this->clearPasswordSession($request);

            return to_route('admin.administrators')->withErrors(['password' => 'Phiên OTP đã hết hạn. Vui lòng yêu cầu lại.']);
        }

        $data = $request->validate(['code' => ['required', 'digits:6']]);

        if ($code->attempts >= 3) {
            $this->clearPasswordSession($request);

            return to_route('admin.administrators')->withErrors(['password' => 'Mã OTP đã bị khóa do nhập sai quá số lần cho phép.']);
        }

        if (! Hash::check($data['code'], (string) $code->code_hash)) {
            $code->increment('attempts');
            $attempts = (int) $code->fresh()->attempts;

            return back()->withErrors(['code' => 'Mã OTP không chính xác. Bạn còn '.max(0, 3 - $attempts).' lần thử.']);
        }

        $code->forceFill(['verified_at' => now()])->save();
        $request->session()->put('admin_security_verified', true);

        return to_route('admin.security.password.reset');
    }

    public function showPasswordForm(Request $request): Response|RedirectResponse
    {
        if (! $this->passwordVerified($request)) {
            return to_route('admin.administrators')->withErrors(['password' => 'Bạn cần xác minh mã OTP trước.']);
        }

        return Inertia::render('admin/ResetAdministratorPassword');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        if (! $this->passwordVerified($request)) {
            return to_route('admin.administrators')->withErrors(['password' => 'Phiên xác minh không hợp lệ.']);
        }

        $validated = $request->validate([
            'password' => $this->passwordRules(),
            'password_confirmation' => ['same:password'],
        ]);

        $user = $this->rootAdmin($request);
        $user->forceFill(['password' => $validated['password']])->save();

        if ($code = $this->pendingCode($request)) {
            $code->update(['used_at' => now()]);
        }

        $this->clearPasswordSession($request);

        return to_route('admin.administrators')->with('success', 'Đã đặt lại mật khẩu tài khoản admin.');
    }

    public function editEmail(Request $request): Response
    {
        return Inertia::render('admin/EditAdministratorEmail', [
            'email' => $request->user()->email,
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $this->rootAdmin($request);

        $data = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        return to_route('admin.administrators')->with('success', 'Đã cập nhật Email tài khoản admin.');
    }

    private function rootAdmin(Request $request)
    {
        return $request->user();
    }

    private function passwordVerified(Request $request): bool
    {
        return $request->session()->get('admin_security_verified', false)
            && $this->pendingCode($request)?->verified_at !== null
            && ! $this->pendingCode($request)?->expired();
    }

    private function pendingCode(Request $request)
    {
        $codeId = $request->session()->get('admin_security_code_id');
        $userId = $request->session()->get('admin_security_user_id');

        if (! $codeId || ! $userId) {
            return null;
        }

        return \App\Models\PasswordResetCode::query()
            ->whereKey($codeId)
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->first();
    }

    private function clearPasswordSession(Request $request): void
    {
        $request->session()->forget(['admin_security_user_id', 'admin_security_code_id', 'admin_security_verified']);
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
