<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminsToBackoffice
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*') || $request->is('logout') || $request->is('.well-known/*')) {
            return $next($request);
        }

        if ($request->user()?->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tài khoản quản trị chỉ được sử dụng trong khu vực quản trị.',
                    'redirect' => url('/admin'),
                ], 403);
            }

            return redirect()->to('/admin');
        }

        return $next($request);
    }
}
