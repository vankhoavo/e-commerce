<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Models\AccountRecoveryRequest;
use App\Models\User;
use App\Services\AccountRecoveryService;
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
        $returnTo = $request->string('redirect')->toString();
        if ($returnTo !== '' && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) $request->session()->put('google_oauth_redirect', $returnTo);
        $state = Str::random(64);
        $request->session()->put('google_oauth_state', $state);
        $query = http_build_query(['client_id'=>$clientId,'redirect_uri'=>$redirectUri,'response_type'=>'code','scope'=>'openid email profile','access_type'=>'online','prompt'=>'select_account','state'=>$state]);
        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = Str::lower(trim($request->string('email')->toString()));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) return response()->json(['exists'=>false,'available'=>false,'google_linked'=>false,'deleted'=>false]);
        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first(['id','google_id','deleted_at']);
        $exists = (bool) $user;
        $deleted = (bool) $user?->deleted_at;
        $googleLinked = (bool) ($user?->google_id);
        return response()->json(['exists'=>$exists,'available'=>!$exists||$googleLinked,'google_linked'=>$googleLinked,'deleted'=>$deleted]);
    }

    public function callback(Request $request, AccountRecoveryService $recoveryService): RedirectResponse
    {
        $state = $request->string('state')->toString();
        $sessionState = $request->session()->pull('google_oauth_state');
        abort_unless($state !== '' && is_string($sessionState) && hash_equals($sessionState, $state), 419);
        if ($request->filled('error')) return redirect()->route('login')->withErrors(['email'=>'Đăng nhập Google đã bị hủy.']);
        $code = $request->string('code')->toString();
        abort_unless($code !== '', 422, 'Google không trả về mã xác thực.');
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', ['code'=>$code,'client_id'=>config('services.google.client_id'),'client_secret'=>config('services.google.client_secret'),'redirect_uri'=>config('services.google.redirect'),'grant_type'=>'authorization_code'])->throw();
        $accessToken = (string) $tokenResponse->json('access_token');
        abort_unless($accessToken !== '', 422, 'Không lấy được mã truy cập từ Google.');
        $googleUser = Http::withToken($accessToken)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        $googleId = data_get($googleUser, 'sub');
        $email = Str::lower((string) data_get($googleUser, 'email'));
        abort_unless($googleId && $email && (bool) data_get($googleUser, 'email_verified', false), 422, 'Tài khoản Google không cung cấp email đã xác minh.');

        $user = User::withTrashed()->where('google_id', $googleId)->first() ?: User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->trashed()) {
            abort_unless($user->role === UserRole::CUSTOMER && $user->is_active, 403, 'Tài khoản không thể khôi phục.');
            $pending = AccountRecoveryRequest::query()->where('user_id', $user->id)->where('status', 'pending_approval')->exists();
            $recovery = $pending
                ? AccountRecoveryRequest::query()->where('user_id', $user->id)->where('status', 'pending_approval')->latest()->firstOrFail()
                : $recoveryService->createOtpRequest($user, 'google');
            $request->session()->put('account_recovery_request_id', $recovery->id);
            return to_route('account.recovery.pending');
        }

        if (! $user) {
            $user = User::create(['name'=>(string)(data_get($googleUser,'name') ?: Str::before($email,'@')),'email'=>$email,'password'=>Str::random(64),'role'=>UserRole::CUSTOMER,'is_active'=>true,'avatar'=>data_get($googleUser,'picture'),'google_id'=>$googleId,'birth_date'=>today()]);
            $user->forceFill(['email_verified_at'=>now()])->save();
        } else {
            abort_unless($user->is_active, 403, 'Tài khoản đã bị khóa.');
            $user->forceFill(['google_id'=>$googleId,'avatar'=>data_get($googleUser,'picture') ?: $user->avatar,'email_verified_at'=>$user->email_verified_at ?: now(),'birth_date'=>$user->birth_date ?: today()])->save();
        }
        Auth::login($user, remember:true);
        $request->session()->regenerate();
        $returnTo = $request->session()->pull('google_oauth_redirect','/');
        if ($user->isStaff()) return redirect()->to('/admin');
        return redirect()->to(is_string($returnTo) && str_starts_with($returnTo,'/') && ! str_starts_with($returnTo,'//') ? $returnTo : '/');
    }
}
