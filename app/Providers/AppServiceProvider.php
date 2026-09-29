<?php

namespace App\Providers;

use App\Filesystem\DatabaseAdapter;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // مُشغّل تخزين "database": الملفات في جدول stored_files (للاستضافة بدون تخزين دائم)
        Storage::extend('database', function ($app, array $config) {
            $adapter = new DatabaseAdapter($app['db']->connection($config['connection'] ?? null), $config['bucket'], $config['url'] ?? null);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        // كشف مشاكل N+1 أثناء التطوير والاختبارات (خطأ بدل استعلامات مخفية)؛ في الإنتاج لا شيء يتوقف
        Model::preventLazyLoading(! $this->app->isProduction());

        // مراقبة الأداء في الإنتاج: أي طلب تتجاوز استعلاماته ثانيتين يُسجَّل في السجلات
        DB::whenQueryingForLongerThan(2000, function ($connection, $event) {
            Log::warning('Slow database queries', [
                'url' => request()->fullUrl(),
                'total_ms' => $connection->totalQueryDuration(),
            ]);
        });

        // كلمات مرور لوحة التحكم: 10 أحرف على الأقل تحتوي حروفًا وأرقامًا
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // حماية تسجيل الدخول من التخمين (Brute force): 5 محاولات/دقيقة لكل بريد + IP
        // + حد بالساعة لكل بريد بغض النظر عن الـ IP (يوقف التخمين الموزّع من أجهزة كثيرة)
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perHour(20)->by('login-email|'.$email),
            ];
        });
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)->by('pwd|'.$request->user()?->id));

        // منع إغراق المتجر بطلبات/رسائل وهمية من نفس الجهاز
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // إعدادات المتجر العامة (واتساب، تكلفة التوصيل...) تُحقن في كل صفحات المتجر
        // حتى يطابق ما يراه العميل ما يحسبه الخادم فعلًا.
        View::composer('layouts.app', function ($view) {
            try {
                $settings = array_filter(Setting::many(Setting::PUBLIC_KEYS), fn ($v) => $v !== null && $v !== '');
            } catch (\Throwable $e) {
                report($e);
                $settings = []; // قاعدة البيانات غير متاحة — تبقى القيم الافتراضية في app.js
            }
            $view->with('publicSettings', $settings);
        });
    }
}
