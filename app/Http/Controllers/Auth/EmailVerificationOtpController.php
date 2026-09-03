<?php

namespace App\Http\Controllers\Auth;

use App\Models\EmailVerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationOtpController
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) return to_route('login');
        if ($request->user()->email_verified_at) return to_route('profile.edit');
        return Inertia::render('auth/VerifyEmailOtp',['email'=>$request->user()->email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data=Validator::make($request->all(),['code'=>['required','digits:6']])->validate();
        $record=EmailVerificationCode::query()->where('user_id',$request->user()->id)->latest()->first();
        if(!$record||$record->email!==$request->user()->email||$record->expired()) return back()->withErrors(['code'=>'Mã xác thực không hợp lệ hoặc đã hết hạn.']);
        if($record->attempts>=5) return back()->withErrors(['code'=>'Bạn đã nhập sai quá số lần cho phép. Vui lòng gửi mã mới.']);
        if(!hash_equals($record->code,$data['code'])){$record->increment('attempts');return back()->withErrors(['code'=>'Mã xác thực không chính xác.']);}
        DB::transaction(function()use($record,$request):void{$record->update(['verified_at'=>now()]);$request->user()->forceFill(['email_verified_at'=>now()])->save();});
        $returnTo=$request->session()->pull('google_verified_return_to')??$request->session()->pull('otp_return_to','/');
        if(!is_string($returnTo)||!str_starts_with($returnTo,'/')||str_starts_with($returnTo,'//'))$returnTo='/';
        return redirect()->to($returnTo)->with('status','email-verified');
    }

    public function resend(Request $request, EmailOtpService $otp): RedirectResponse
    {
        if($request->user()->email_verified_at) return to_route('profile.edit');
        $otp->send($request->user(),$request->user()->email);
        return back()->with('status','verification-code-sent');
    }
}
