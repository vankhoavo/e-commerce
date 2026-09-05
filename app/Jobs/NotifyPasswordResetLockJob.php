<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyPasswordResetLockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $lockedUserId,
        public readonly string $lockedUserName,
        public readonly string $lockedUserEmail,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('EMAIL_LOCK_JOB: started.', [
            'locked_user_id' => $this->lockedUserId,
            'locked_user_email' => $this->lockedUserEmail,
            'queue' => $this->queue,
        ]);

        $recipients = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::STAFF->value])
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning('EMAIL_LOCK_JOB: no active admin/staff recipients.', ['locked_user_id' => $this->lockedUserId]);
            return;
        }

        $subject = 'Cảnh báo khóa tài khoản do OTP khôi phục mật khẩu - TechStore';
        $message = "Tài khoản khách hàng đã bị khóa do nhập sai mã OTP khôi phục mật khẩu 5 lần.\n\n"
            ."Khách hàng: {$this->lockedUserName}\n"
            ."Email: {$this->lockedUserEmail}\n"
            ."User ID: {$this->lockedUserId}\n\n"
            ."Vui lòng kiểm tra tài khoản và thực hiện phê duyệt/mở khóa theo quy trình quản trị của TechStore.";

        foreach ($recipients as $email) {
            try {
                Log::info('EMAIL_LOCK_JOB: sending SMTP email.', ['locked_user_id' => $this->lockedUserId, 'recipient' => $email]);
                Mail::raw($message, function ($mail) use ($email, $subject): void {
                    $mail->to($email)->subject($subject);
                });
                Log::info('EMAIL_LOCK_JOB: SMTP send completed.', ['locked_user_id' => $this->lockedUserId, 'recipient' => $email]);
            } catch (\Throwable $exception) {
                Log::error('EMAIL_LOCK_JOB: SMTP send failed.', ['locked_user_id' => $this->lockedUserId, 'recipient' => $email, 'exception' => get_class($exception), 'error' => $exception->getMessage(), 'attempt' => $this->attempts()]);
                throw $exception;
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EMAIL_LOCK_JOB: permanently failed.', ['locked_user_id' => $this->lockedUserId, 'locked_user_email' => $this->lockedUserEmail, 'exception' => get_class($exception), 'error' => $exception->getMessage()]);
    }
}
