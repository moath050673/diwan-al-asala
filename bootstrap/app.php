<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // الاستضافة (Laravel Cloud) تمرر الطلبات عبر موازن أحمال: بدون هذا يُعامل كل العملاء
        // كأنهم IP واحد (فيشتركون في حد الطلبات)، ولا يُكتشف أن الاتصال HTTPS.
        $middleware->trustProxies(at: '*');
        // لا يوجد مسار باسم login — بدون هذا يسبب أي طلب API غير مسجّل خطأ 500 بدل 401
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/admin');
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // الواجهة الأمامية ترسل طلبات fetch بدون ترويسة Accept: application/json،
        // لذا نجبر مسارات /api على إرجاع JSON دائمًا (بدل إعادة التوجيه عند فشل التحقق).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // كل خطأ يُسجَّل مع الصفحة التي حدث فيها (يسهّل تتبعه في Logs على Laravel Cloud)
        $exceptions->context(fn () => app()->runningInConsole() ? [] : [
            'url' => request()->method().' '.request()->fullUrl(),
            'ip' => request()->ip(),
        ]);

        // تنبيه فوري على Telegram للأخطاء الحقيقية (500...) — التسجيل العادي في السجل يستمر كما هو
        $exceptions->reportable(fn (\Throwable $e) => app(\App\Services\ErrorAlerter::class)->alert($e));
    })->create();

return $app;
