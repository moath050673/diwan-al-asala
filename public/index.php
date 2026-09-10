<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// تحديد ما إذا كان التطبيق في وضع الصيانة
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// تسجيل الـ Autoloader لمكتبات Composer
require __DIR__.'/../vendor/autoload.php';

// تشغيل التطبيق ومعالجة الطلب الحالي
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
