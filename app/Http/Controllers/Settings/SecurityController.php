<?php
namespace App\Http\Controllers\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Mail\PasswordChangedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
class SecurityController extends Controller
{
    public function edit(TwoFactorAuthenticationRequest$request):Response{$props=['canManageTwoFactor'=>Features::canManageTwoFactorAuthentication(),'canManagePasskeys'=>Features::canManagePasskeys(),'passkeys'=>Features::canManagePasskeys()?$request->user()->passkeys()->select(['id','name','credential','created_at','last_used_at'])->latest()->get()->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'authenticator'=>$p->authenticator,'created_at_diff'=>$p->created_at->diffForHumans(),'last_used_at_diff'=>$p->last_used_at?->diffForHumans()])->values()->all():[],'passwordRules'=>Password::defaults()->toPasswordRulesString()];if(Features::canManageTwoFactorAuthentication()){$request->ensureStateIsValid();$props['twoFactorEnabled']=$request->user()->hasEnabledTwoFactorAuthentication();$props['requiresConfirmation']=Features::optionEnabled(Features::twoFactorAuthentication(),'confirm');}return Inertia::render('settings/Security',$props);}
    public function update(PasswordUpdateRequest$request):RedirectResponse{$user=$request->user();$user->update(['password'=>$request->password]);try{Mail::to($user->email)->send(new PasswordChangedMail($user));}catch(\Throwable$e){report($e);}Inertia::flash('toast',['type'=>'success','message'=>'Mật khẩu đã được cập nhật.']);return back();}
}
