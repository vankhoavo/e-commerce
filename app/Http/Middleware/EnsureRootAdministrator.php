<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRootAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->is_active
            && $user->name === 'admin'
            && $user->isAdmin()
            && $user->role->value === 'admin',
            403,
            'Chỉ tài khoản admin có toàn quyền quản trị mới được thực hiện thao tác này.',
        );

        return $next($request);
    }
}
