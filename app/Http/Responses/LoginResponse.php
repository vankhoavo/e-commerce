<?php

namespace App\Http\Responses;

use App\Services\EmailOtpService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly EmailOtpService $otp) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) return new JsonResponse(['two_factor'=>false],200);
        $user=$request->user();
        if ($user?->isAdmin()) return redirect()->to('/admin');
        if ($user && ! $user->email_verified_at) {
            $this->otp->send($user,$user->email);
            $returnTo=$request->session()->pull('url.intended','/');
            if (is_string($returnTo) && str_starts_with($returnTo,'/') && !str_starts_with($returnTo,'//')) $request->session()->put('otp_return_to',$returnTo);
            return redirect()->route('email-verify-otp.show');
        }
        return redirect()->intended('/');
    }
}
