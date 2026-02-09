<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SARH Hardened: Trust all proxies (Hostinger shared hosting reverse proxy).
        // Without this, $request->isSecure() returns false behind HTTPS proxy,
        // causing CSRF token / session cookie mismatches → 419 errors.
        $middleware->trustProxies(at: '*');

        // v1.9.0: إصلاح خطأ Permissions-Policy الذي ظهر في v1.8.x
        // بدون هذا، المتصفح يرفض طلب navigator.geolocation.getCurrentPosition()
        // مما يُعطّل تسجيل الحضور الجغرافي في بوابة الموظفين /app
        $middleware->append(\App\Http\Middleware\SetPermissionsPolicy::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
