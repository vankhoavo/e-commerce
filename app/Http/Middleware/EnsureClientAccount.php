<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user?->isBackOffice(), 403, 'Tài khoản quản trị và nhân sự không được phép truy cập giao diện khách hàng.');
        return $next($request);
    }
}
