<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isBackOffice(), 403, 'Tài khoản không có quyền truy cập khu vực quản trị.');
        return $next($request);
    }
}
