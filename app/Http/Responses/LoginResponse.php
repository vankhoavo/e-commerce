<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        $user = $request->user();

        if ($user && $user->isStaff()) {
            $request->session()->forget('url.intended');
            return redirect()->to('/admin');
        }

        if ($user && $user->registration_pending) {
            return redirect()->route('email-verify-otp.show')
                ->withErrors(['email' => 'Vui lòng xác thực Email trước khi tiếp tục.']);
        }

        if ($user && ! $user->email_verified_at) {
            return redirect()->route('email-verify-otp.show');
        }

        return redirect()->intended('/');
    }
}
