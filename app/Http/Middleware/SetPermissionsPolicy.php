<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SARH v1.9.0 — إصلاح خطأ Permissions-Policy / Geolocation
 *
 * في v1.8.x كان المتصفح يرفض navigator.geolocation.getCurrentPosition()
 * لأن الـ header الافتراضي (أو عدم وجوده) يحظر geolocation على shared hosting.
 *
 * هذا الـ Middleware يُضاف عالمياً في bootstrap/app.php ويسمح بـ geolocation لـ self فقط.
 */
class SetPermissionsPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // السماح بـ geolocation لنفس الدومين فقط — ضروري لتسجيل الحضور
        // حظر الكاميرا والميكروفون والدفع لعدم الحاجة لها
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(), microphone=(), payment=()'
        );

        return $response;
    }
}
