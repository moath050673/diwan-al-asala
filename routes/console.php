<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('عرض اقتباس ملهم عشوائي');

// حذف رموز الدخول (Sanctum tokens) المنتهية يوميًا — يتطلب تشغيل php artisan schedule:run عبر cron
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// نسخة احتياطية يومية لقاعدة البيانات (بما فيها الصور) الساعة 3 فجرًا بتوقيت اليمن
Schedule::command('backup:run')->dailyAt('03:00')->timezone('Asia/Aden')->withoutOverlapping();
