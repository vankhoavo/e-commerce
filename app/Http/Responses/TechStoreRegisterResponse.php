<?php

namespace App\Http\Responses;

use App\Services\EmailOtpService;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TechStoreRegisterResponse implements RegisterResponseContract
{
    public function __construct(private readonly EmailOtpService $otp) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false], 201);
        }

        $user = $request->user();

        if ($user && ! $user->email_verified_at && ! $user->google_id) {
            $this->otp->send($user, $user->email);
            return redirect()->route('email-verify-otp.show');
        }

        return redirect()->intended('/')->with('status', 'registration-success');
    }
}
