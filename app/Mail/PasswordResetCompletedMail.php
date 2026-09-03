<?php
namespace App\Mail;
use App\Models\User;
use Illuminate\Mail\Mailable;
class PasswordResetCompletedMail extends Mailable{public function __construct(public User $user){}public function build(){return $this->subject('TechStore - Khôi phục mật khẩu thành công')->view('emails.account.password-reset');}}
