<?php

namespace App\Services;

use App\Jobs\SendEmailOtpJob;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmailOtpService
{
    private const OTP_TTL_MINUTES = 1;

    public function send(User $user, string $email): EmailVerificationCode
    {
        $email = mb_strtolower(trim($email));

        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);
        $record = EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'email' => $email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
        ]);

        SendEmailOtpJob::dispatch(
            recordId: $record->id,
            userId: $user->id,
            email: $email,
            code: $code,
            subject: 'Mã xác thực Email - TechStore',
            message: "Mã xác thực TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 1 phút. Không chia sẻ mã này với bất kỳ ai.",
        );

        return $record;
    }
}
