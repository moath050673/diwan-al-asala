<?php

/*
 * إعدادات خاصة بالمتجر تُقرأ من .env.
 * لا تستخدم env() مباشرة خارج ملفات config — لأنها تُرجع null بعد تشغيل php artisan config:cache.
 */
return [
    'name' => env('STORE_NAME', 'متجر ديوان الأصالة'),
    'currency' => env('STORE_CURRENCY', 'ريال يمني'),
    'owner_email' => env('STORE_OWNER_EMAIL'),
    'whatsapp_number' => env('WHATSAPP_NUMBER', '967700000000'),
    'facebook_url' => env('FACEBOOK_URL', 'https://facebook.com/'),
    'instagram_url' => env('INSTAGRAM_URL', 'https://instagram.com/'),
    'shipping_cost_sanaa' => env('SHIPPING_COST_SANAA', 1500),

    'jib' => [
        'account_name' => env('JIB_ACCOUNT_NAME', 'متجر ديوان الأصالة'),
        'account_number' => env('JIB_ACCOUNT_NUMBER', 'PLACEHOLDER'),
    ],
    'kareemi' => [
        'account_name' => env('KAREEMI_ACCOUNT_NAME', 'متجر ديوان الأصالة'),
        'account_number' => env('KAREEMI_ACCOUNT_NUMBER', 'PLACEHOLDER'),
    ],

    // بيانات المدير الأول (تُستخدم فقط في AdminUserSeeder)
    'admin' => [
        'name' => env('ADMIN_NAME', 'مدير المتجر'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // أين تُحفظ الملفات المرفوعة (أي قرص من config/filesystems.php):
    // - محليًا: public / local (الافتراضي)
    // - Laravel Cloud Starter (ملفات السيرفر تُمسح مع كل نشر/سبات): db_public / db_private
    // - لاحقًا مع Object Storage أو S3: اسم قرص الـ Bucket — بدون أي تعديل في الكود
    'disks' => [
        'images' => env('PRODUCT_IMAGES_DISK', 'public'),   // صور المنتجات (عامة)
        'receipts' => env('RECEIPTS_DISK', 'local'),        // إيصالات الدفع (خاصة — للمدير فقط)
    ],

    // إشعارات الطلبات الجديدة عبر Telegram (يمكن وضع أكثر من chat_id مفصولة بفاصلة)
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    // الحد الأقصى لعدد العناصر في صفحة واحدة من أي قائمة في الـ API
    'max_per_page' => 100,
];
