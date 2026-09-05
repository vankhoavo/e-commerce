<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user && $user->registration_pending) {
            $redirect = route('email-verify-otp.show');
        } elseif ($user?->isStaff()) {
            $redirect = '/admin';
        } else {
            $redirect = Fortify::redirects('login') ?: '/';
        }

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => $redirect], 200)
            : redirect()->to($redirect);
    }
}
