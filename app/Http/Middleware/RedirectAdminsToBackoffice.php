<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminsToBackoffice
{
    private const ADMIN_PREFIX = 'admin';

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user?->isAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tài khoản quản trị chỉ được sử dụng trong khu vực quản trị.',
                'redirect' => url('/admin'),
            ], 403);
        }

        return redirect()->to('/admin');
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is(self::ADMIN_PREFIX)
            || $request->is(self::ADMIN_PREFIX.'/*')
            || $request->is('logout')
            || $request->is('auth/google*')
            || $request->is('.well-known/*');
    }
}
