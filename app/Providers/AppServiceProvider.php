<?php

namespace App\Providers;

use App\Filesystem\DatabaseAdapter;
use App\Models\Setting;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        // حماية تسجيل الدخول من التخمين (Brute force): 5 محاولات/دقيقة لكل بريد + IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

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
