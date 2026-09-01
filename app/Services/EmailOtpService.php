<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public function send(User $user, string $email): void
    {
        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::query()->where('user_id', $user->id)->delete();

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::raw("Mã xác thực TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 10 phút. Không chia sẻ mã này với bất kỳ ai.", function ($message) use ($email): void {
            $message->to($email)->subject('Mã xác thực Email - TechStore');
        });
    }
}
