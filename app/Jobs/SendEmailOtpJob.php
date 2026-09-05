<?php

namespace App\Jobs;

use App\Models\AccountRecoveryRequest;
use App\Models\EmailVerificationCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $recordId,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $code,
        public readonly string $subject,
        public readonly string $message,
        public readonly string $type = 'verification',
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        if ($this->type === 'account_recovery') {
            $record = AccountRecoveryRequest::query()
                ->whereKey($this->recordId)
                ->where('user_id', $this->userId)
                ->where('email', $this->email)
                ->where('status', 'pending_otp')
                ->first();
        } else {
            $record = $this->type === 'password_reset'
                ? PasswordResetCode::query()->whereKey($this->recordId)->where('user_id', $this->userId)->where('email', $this->email)->whereNull('used_at')->first()
                : EmailVerificationCode::query()->whereKey($this->recordId)->where('user_id', $this->userId)->where('email', $this->email)->whereNull('verified_at')->first();
        }

        if (! $record) {
            return;
        }

        // AccountRecoveryRequest dùng otpExpired(), còn các loại OTP cũ dùng expired().
        if ($this->type === 'account_recovery') {
            if ($record->otpExpired()) {
                return;
            }
        } elseif ($record->expired()) {
            return;
        }

        // Tài khoản khôi phục đã Soft Delete vẫn phải được phép gửi OTP.
        $userQuery = User::query();
        if ($this->type === 'account_recovery') {
            $userQuery->withTrashed();
        }

        $user = $userQuery->whereKey($this->userId)->first();
        if (! $user || ! $user->is_active) {
            return;
        }

        Mail::raw($this->message, function ($mail): void {
            $mail->to($this->email)->subject($this->subject);
        });
    }
}
