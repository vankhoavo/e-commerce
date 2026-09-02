<?php

namespace App\Http\Responses;

use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Auth;
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
        $usedGoogleLinkedEmail = (bool) ($user?->google_id);

        if ($user && ! $user->email_verified_at && ! $usedGoogleLinkedEmail) {
            $this->otp->send($user, $user->email);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'status',
            $usedGoogleLinkedEmail ? 'google-password-created' : 'registration-success',
        );
    }
}
