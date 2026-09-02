<?php

namespace App\Http\Controllers\Auth;

use App\Models\PhonePasswordResetToken;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PhonePasswordResetController
{
    public function showRequest(): Response
    {
        return Inertia::render('auth/ForgotPasswordPhone', [
            'status' => session('status'),
        ]);
    }

    public function requestCode(Request $request, SmsService $sms): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{8,30}$/'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $key = 'phone-reset:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Vui lòng thử lại sau {$seconds} giây."]);
        }
        RateLimiter::hit($key, 300);

        $storedCandidates = array_values(array_unique([
            $phone,
            ltrim($phone, '+'),
            $phone !== '' && Str::startsWith($phone, '+84') ? '0'.substr($phone, 3) : $phone,
        ]));
        $user = User::query()->whereIn('phone', $storedCandidates)->first();
        if (! $user || ! $user->is_active) {
            return back()->withErrors(['phone' => 'Không tìm thấy tài khoản đang hoạt động với số điện thoại này.']);
        }

        $canonicalPhone = $this->normalizePhone((string) $user->phone);
        PhonePasswordResetToken::query()
            ->where('phone', $canonicalPhone)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);
        PhonePasswordResetToken::query()->create([
            'user_id' => $user->id,
            'phone' => $canonicalPhone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        try {
            $sms->send($canonicalPhone, "TechStore: Ma OTP khoi phuc mat khau cua ban la {$code}. Ma co hieu luc trong 10 phut.");
        } catch (\Throwable $e) {
            report($e);
            PhonePasswordResetToken::query()->where('phone', $canonicalPhone)->whereNull('used_at')->delete();
            return back()->withErrors(['phone' => 'Không thể gửi mã OTP. Vui lòng kiểm tra cấu hình SMS.']);
        }

        session([
            'phone_password_reset_phone' => $canonicalPhone,
            'phone_password_reset_user_id' => $user->id,
        ]);

        return to_route('password.phone.verify')->with('status', 'Mã OTP đã được gửi đến số điện thoại của bạn.');
    }

    public function showVerify(Request $request): Response
    {
        return Inertia::render('auth/VerifyPhonePasswordReset', [
            'phone' => session('phone_password_reset_phone'),
            'status' => session('status'),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $phone = session('phone_password_reset_phone');
        $userId = session('phone_password_reset_user_id');

        if (! $phone || ! $userId) {
            return to_route('password.phone.request')->withErrors(['phone' => 'Phiên khôi phục đã hết hạn.']);
        }

        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $token = PhonePasswordResetToken::query()
            ->where('phone', $phone)
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $token || $token->expires_at->isPast()) {
            return back()->withErrors(['code' => 'Mã OTP đã hết hạn.']);
        }

        if ($token->attempts >= 5 || ! Hash::check($validated['code'], $token->code_hash)) {
            $token->increment('attempts');
            return back()->withErrors(['code' => 'Mã OTP không đúng.']);
        }

        $token->update(['used_at' => now()]);
        session(['phone_password_reset_verified' => true]);

        return to_route('password.phone.reset');
    }

    public function showReset(): Response|RedirectResponse
    {
        if (! session('phone_password_reset_verified') || ! session('phone_password_reset_user_id')) {
            return to_route('password.phone.request');
        }

        return Inertia::render('auth/ResetPhonePassword');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $userId = session('phone_password_reset_user_id');
        if (! session('phone_password_reset_verified') || ! $userId) {
            return to_route('password.phone.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->find($userId);
        if (! $user) {
            return to_route('password.phone.request')->withErrors(['phone' => 'Không tìm thấy tài khoản.']);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        session()->forget(['phone_password_reset_phone', 'phone_password_reset_user_id', 'phone_password_reset_verified']);

        return to_route('login')->with('status', 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
        if (Str::startsWith($phone, '84') && ! Str::startsWith($phone, '+')) $phone = '+'.$phone;
        if (Str::startsWith($phone, '0')) $phone = '+84'.substr($phone, 1);
        return $phone;
    }
}
