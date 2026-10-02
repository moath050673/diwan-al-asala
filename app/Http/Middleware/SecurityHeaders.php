<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ترويسات أمان لكل الاستجابات (منع التضمين في iframe، منع تخمين نوع المحتوى، CSP...).
 */
class SecurityHeaders
{
    /**
     * Content-Security-Policy: الصفحات تحمّل سكربتاتها من نفس الموقع فقط (three.js مستضاف محليًا)
     * والخطوط من Google Fonts. 'unsafe-inline' مطلوب لأن الصفحات تستخدم سكربتات داخلية
     * (إعدادات المتجر، معرض الصور...). الحماية الأساسية من XSS هي escapeHtml في كل القوالب.
     */
    private const CSP = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' data: https://fonts.gstatic.com; "
        ."img-src 'self' data: blob: https:; "
        ."connect-src 'self'; "
        ."frame-src 'none'; "
        ."object-src 'none'; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // geolocation=(self): زر "موقعي الحالي" في صفحة إتمام الطلب (الموقع نفسه فقط، لا أي iframe)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', self::CSP);
        // لا نكشف نسخة PHP (PHP يضيف هذه الترويسة بنفسه خارج استجابة Laravel)
        $response->headers->remove('X-Powered-By');
        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }

        // لوحة التحكم والـ API لا تظهر في محركات البحث
        if ($request->is('admin', 'admin/*', 'api/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // ردود لوحة التحكم (بيانات العملاء والطلبات) لا تُحفظ في ذاكرة المتصفح أو أي وسيط
        if ($request->is('api/*') && $request->bearerToken()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
