<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');

        abort_unless($clientId && $redirectUri, 503, 'Google OAuth chưa được cấu hình.');

        $state = Str::random(64);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = Str::lower(trim($request->string('email')->toString()));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['google_linked' => false]);
        }

        return response()->json([
            'google_linked' => User::query()
                ->where('email', $email)
                ->whereNotNull('google_id')
                ->exists(),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->string('state')->toString();
        $sessionState = $request->session()->pull('google_oauth_state');

        abort_unless($state !== '' && is_string($sessionState) && hash_equals($sessionState, $state), 419);

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Đăng nhập Google đã bị hủy.',
            ]);
        }

        $code = $request->string('code')->toString();
        abort_unless($code !== '', 422, 'Google không trả về mã xác thực.');

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ])->throw();

        $accessToken = (string) $tokenResponse->json('access_token');
        abort_unless($accessToken !== '', 422, 'Không lấy được mã truy cập từ Google.');

        $googleUser = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->throw()
            ->json();

        $googleId = data_get($googleUser, 'sub');
        $email = Str::lower((string) data_get($googleUser, 'email'));
        $emailVerified = (bool) data_get($googleUser, 'email_verified', false);

        abort_unless($googleId && $email && $emailVerified, 422, 'Tài khoản Google không cung cấp email đã xác minh.');

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => (string) (data_get($googleUser, 'name') ?: Str::before($email, '@')),
                'email' => $email,
                'password' => Str::random(64),
                'role' => UserRole::CUSTOMER,
                'is_active' => true,
                'avatar' => data_get($googleUser, 'picture'),
                'google_id' => $googleId,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
        } else {
            abort_unless($user->is_active, 403, 'Tài khoản đã bị khóa.');

            $user->forceFill([
                'google_id' => $googleId,
                'avatar' => data_get($googleUser, 'picture') ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
