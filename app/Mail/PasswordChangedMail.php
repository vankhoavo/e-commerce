<?php
namespace App\Mail;
use App\Models\User;
use Illuminate\Mail\Mailable;
class PasswordChangedMail extends Mailable{public function __construct(public User $user){}public function build(){return $this->subject('TechStore - Mật khẩu đã được thay đổi')->view('emails.account.password-changed');}}
