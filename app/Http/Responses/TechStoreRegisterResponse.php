<?php
namespace App\Http\Responses;
use App\Mail\WelcomeMail;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;
class TechStoreRegisterResponse implements RegisterResponseContract
{
    public function __construct(private readonly EmailOtpService$otp){}
    public function toResponse($request):Response{if($request->wantsJson())return response()->json(['two_factor'=>false],201);$user=$request->user();if($user){try{Mail::to($user->email)->send(new WelcomeMail($user));}catch(\Throwable$e){report($e);}if(!$user->email_verified_at){$this->otp->send($user,$user->email);return redirect()->route('email-verify-otp.show')->with('status','registration-success');}}return redirect()->intended('/');}
}
