<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public function send(User $user, string $email): EmailVerificationCode
    {
        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $record = EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'email' => mb_strtolower(trim($email)),
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        try {
            Mail::raw("Mã xác thực TechStore của bạn là: {$code}\n\nMã có hiệu lực trong 10 phút. Không chia sẻ mã này với bất kỳ ai.", function ($message) use ($email): void {
                $message->to($email)->subject('Mã xác thực Email - TechStore');
            });
        } catch (\Throwable $exception) {
            $record->delete();
            throw $exception;
        }

        return $record;
    }
}
