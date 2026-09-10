<?php

return [
    'name' => env('APP_NAME', 'متجر ديوان الأصالة'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Aden'),
    'locale' => env('APP_LOCALE', 'ar'),
    'fallback_locale' => 'en',
    'faker_locale' => 'ar_SA',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'maintenance' => [
        'driver' => 'file',
    ],
];
