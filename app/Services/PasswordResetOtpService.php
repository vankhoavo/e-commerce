<?php

namespace App\Services;

use App\Jobs\SendEmailOtpJob;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtpService
{
    public const OTP_TTL_SECONDS = 60;

    public function send(User $user): PasswordResetCode
    {
        PasswordResetCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $resetCode = PasswordResetCode::query()->create([
            'user_id' => $user->id,
            'email' => mb_strtolower(trim($user->email)),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS),
            'attempts' => 0,
        ]);

        SendEmailOtpJob::dispatch(
            recordId: $resetCode->id,
            userId: $user->id,
            email: $resetCode->email,
            code: $code,
            subject: 'Mã OTP khôi phục mật khẩu - TechStore',
            message: "Mã OTP khôi phục mật khẩu TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 1 phút. Không chia sẻ mã này cho bất kỳ ai.",
            type: 'password_reset',
        );

        return $resetCode;
    }
}
