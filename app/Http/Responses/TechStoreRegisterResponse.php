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
        $user = $request->user();

        if (! $user) {
            return redirect()->route('register');
        }

        if ($user->google_id || $user->email_verified_at) {
            return redirect()->intended('/')->with('status', 'registration-success');
        }

        try {
            $this->otp->send($user, $user->email);
        } catch (\Throwable $exception) {
            report($exception);
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('register')->withErrors([
                'email' => 'Không thể gửi mã OTP đến email. Vui lòng kiểm tra cấu hình email của hệ thống và thử lại.',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false, 'email_verification_required' => true], 201);
        }

        return redirect()->route('email-verify-otp.show');
    }
}
