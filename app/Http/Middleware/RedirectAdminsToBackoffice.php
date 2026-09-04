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
            return redirect()->to('/admin');
        }

        return $next($request);
    }
}
