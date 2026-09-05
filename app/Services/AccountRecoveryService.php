<?php

namespace App\Services;

use App\Jobs\SendEmailOtpJob;
use App\Models\AccountRecoveryRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AccountRecoveryService
{
    public const OTP_TTL_SECONDS = 60;
    public const MAX_SENDS = 5;
    public const SEND_COOLDOWN_SECONDS = 60;
    public const SEND_LOCKOUT_SECONDS = 3600;

    public function createOtpRequest(User $user, string $method): AccountRecoveryRequest
    {
        AccountRecoveryRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending_otp', 'otp_verified', 'pending_approval'])
            ->update(['status' => 'cancelled']);

        $code = (string) random_int(100000, 999999);
        $request = AccountRecoveryRequest::query()->create([
            'user_id' => $user->id,
            'email' => Str::lower(trim((string) $user->email)),
            'method' => $method,
            'status' => $method === 'google' ? 'pending_approval' : 'pending_otp',
            'otp_hash' => $method === 'google' ? null : Hash::make($code),
            'otp_expires_at' => $method === 'google' ? null : now()->addSeconds(self::OTP_TTL_SECONDS),
        ]);

        if ($method !== 'google') {
            SendEmailOtpJob::dispatch(
                recordId: $request->id,
                userId: $user->id,
                email: $request->email,
                code: $code,
                subject: 'Mã OTP khôi phục tài khoản - TechStore',
                message: "Mã OTP khôi phục tài khoản TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 1 phút. Không chia sẻ mã này cho bất kỳ ai.",
                type: 'account_recovery',
            );
        }

        return $request;
    }

    public function verifyOtp(AccountRecoveryRequest $request, string $code): bool
    {
        if ($request->status !== 'pending_otp' || $request->otpExpired() || blank($request->otp_hash)) return false;
        if ($request->otp_attempts >= 5) return false;
        if (! Hash::check($code, $request->otp_hash)) {
            $request->increment('otp_attempts');
            return false;
        }

        $request->forceFill([
            'status' => 'pending_approval',
            'otp_verified_at' => now(),
            'otp_hash' => null,
        ])->save();

        return true;
    }

    public function sendKey(string $purpose, string $email, ?string $ip = null): string
    {
        return 'otp-send:'.$purpose.':'.sha1(Str::lower(trim($email)).'|'.($ip ?? ''));
    }

    public function availableSendDelay(string $purpose, string $email, ?string $ip = null): int
    {
        return RateLimiter::availableIn($this->sendKey($purpose, $email, $ip));
    }

    public function canSend(string $purpose, string $email, ?string $ip = null): bool
    {
        return ! RateLimiter::tooManyAttempts($this->sendKey($purpose, $email, $ip), self::MAX_SENDS);
    }

    public function registerSend(string $purpose, string $email, ?string $ip = null): void
    {
        RateLimiter::hit($this->sendKey($purpose, $email, $ip), self::SEND_LOCKOUT_SECONDS);
    }
}
