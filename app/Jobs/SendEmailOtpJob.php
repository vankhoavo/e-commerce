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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $recordId,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $code,
        public readonly string $subject,
        public readonly string $message,
        public readonly string $type = 'verification',
    ) {
        // Laravel Cloud currently provides the Managed Queue as `default`.
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('EMAIL_OTP_JOB: started.', [
            'record_id' => $this->recordId,
            'user_id' => $this->userId,
            'email' => $this->email,
            'type' => $this->type,
            'queue' => $this->queue,
        ]);

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
            Log::warning('EMAIL_OTP_JOB: verification record not found or no longer pending.', [
                'record_id' => $this->recordId,
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
            ]);
            return;
        }

        // OTP hết hạn chỉ ngăn việc xác minh OTP; không được ngăn Email Job đã hợp lệ gửi Email.
        // Nếu Job chạy chậm hơn 60 giây, Email vẫn phải được gửi để người dùng có thể nhận mã và hệ thống
        // sẽ từ chối mã đó tại bước verify() nếu đã hết hạn.

        $userQuery = User::query();
        if ($this->type === 'account_recovery') {
            $userQuery->withTrashed();
        }

        $user = $userQuery->whereKey($this->userId)->first();
        if (! $user || ! $user->is_active) {
            Log::warning('EMAIL_OTP_JOB: user unavailable or inactive.', [
                'record_id' => $this->recordId,
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
            ]);
            return;
        }

        try {
            Log::info('EMAIL_OTP_JOB: sending SMTP email.', [
                'record_id' => $this->recordId,
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
            ]);

            Mail::raw($this->message, function ($mail): void {
                $mail->to($this->email)->subject($this->subject);
            });

            Log::info('EMAIL_OTP_JOB: SMTP send completed.', [
                'record_id' => $this->recordId,
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
            ]);
        } catch (\Throwable $exception) {
            Log::error('EMAIL_OTP_JOB: SMTP send failed.', [
                'record_id' => $this->recordId,
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
                'exception' => get_class($exception),
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EMAIL_OTP_JOB: permanently failed.', [
            'record_id' => $this->recordId,
            'user_id' => $this->userId,
            'email' => $this->email,
            'type' => $this->type,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);
    }
}
