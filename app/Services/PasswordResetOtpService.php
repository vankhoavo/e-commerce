<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetOtpService
{
    public function send(User $user): PasswordResetCode
    {
        PasswordResetCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $resetCode = PasswordResetCode::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        try {
            Mail::raw(
                "Mã OTP khôi phục mật khẩu TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 10 phút. Không chia sẻ mã này với bất kỳ ai.",
                function ($message) use ($user): void {
                    $message->to($user->email)->subject('Mã OTP khôi phục mật khẩu - TechStore');
                },
            );
        } catch (\Throwable $exception) {
            $resetCode->delete();
            throw $exception;
        }

        return $resetCode;
    }
}
