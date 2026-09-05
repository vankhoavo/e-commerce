<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyPasswordResetLockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $lockedUserId,
        public readonly string $lockedUserName,
        public readonly string $lockedUserEmail,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::STAFF->value])
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $subject = 'Cảnh báo khóa tài khoản do OTP khôi phục mật khẩu - TechStore';
        $message = "Tài khoản khách hàng đã bị khóa do nhập sai mã OTP khôi phục mật khẩu 3 lần.\n\n"
            ."Khách hàng: {$this->lockedUserName}\n"
            ."Email: {$this->lockedUserEmail}\n"
            ."User ID: {$this->lockedUserId}\n\n"
            ."Vui lòng kiểm tra tài khoản và thực hiện phê duyệt/mở khóa theo quy trình quản trị của TechStore.";

        foreach ($recipients as $email) {
            Mail::raw($message, function ($mail) use ($email, $subject): void {
                $mail->to($email)->subject($subject);
            });
        }
    }
}
