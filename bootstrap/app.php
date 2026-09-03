<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureClientAccount;
use App\Http\Middleware\EnsureEmailOtpVerified;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php',commands: __DIR__.'/../routes/console.php',health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance','sidebar_state']);
        $middleware->alias(['admin'=>EnsureAdmin::class,'admin.permission'=>EnsureAdminPermission::class,'otp.verified'=>EnsureEmailOtpVerified::class,'client'=>EnsureClientAccount::class]);
        $middleware->web(append: [HandleAppearance::class,HandleInertiaRequests::class,AddLinkHeadersForPreloadedAssets::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($request->expectsJson()) return null;
            $status = $e->getStatusCode();
            if (in_array($status, [403,404,429,500,503], true)) return \Inertia\Inertia::render('errors/Status', ['status'=>$status])->toResponse($request)->setStatusCode($status);
            return null;
        });
    })->create();
