<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // ---------- تخزين دائم داخل قاعدة البيانات (Laravel Cloud Starter بدون Buckets) ----------
        // صور المنتجات: تُعرض للزوار عبر /media/{path}
        'db_public' => [
            'driver' => 'database',
            'bucket' => 'public',
            'url' => '/media',
            'visibility' => 'public',
            'throw' => false,
        ],
        // إيصالات الدفع: لا تُعرض إلا للمدير عبر /api/payments/{id}/receipt
        'db_private' => [
            'driver' => 'database',
            'bucket' => 'private',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
