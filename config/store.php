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
    // الافتراضي في الإنتاج: قاعدة البيانات — ملفات السيرفر على الاستضافات السحابية تُمسح مع كل نشر،
    // فلا نعتمد على تذكّر ضبط المتغيرات يدويًا حتى لا تضيع الصور والإيصالات.
    'disks' => [
        'images' => env('PRODUCT_IMAGES_DISK') ?: (env('APP_ENV') === 'production' ? 'db_public' : 'public'),   // صور المنتجات (عامة)
        'receipts' => env('RECEIPTS_DISK') ?: (env('APP_ENV') === 'production' ? 'db_private' : 'local'),       // إيصالات الدفع (خاصة — للمدير فقط)
    ],

    // إشعارات الطلبات الجديدة عبر Telegram (يمكن وضع أكثر من chat_id مفصولة بفاصلة)
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    // تنبيه على Telegram عند حدوث خطأ في الموقع (نفس البوت ونفس المحادثة) — مرة كل 30 دقيقة لنفس الخطأ
    'error_alerts' => [
        'telegram' => (bool) env('ERROR_ALERTS_TELEGRAM', true),
    ],

    // النسخ الاحتياطي لقاعدة البيانات (php artisan backup:run / backup:restore)
    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),            // أين تُحفظ النسخة (local = storage/app/private/backups)
        'keep' => (int) env('BACKUP_KEEP', 7),             // عدد النسخ المحفوظة على القرص
        'telegram' => (bool) env('BACKUP_TELEGRAM', false), // إرسال النسخة لمحادثة Telegram (نسخة خارج السيرفر)
        'password' => env('BACKUP_PASSWORD'),              // تشفير النسخة (إلزامي عند الإرسال لـ Telegram)
    ],

    // تحسين الصور المرفوعة تلقائيًا (يتطلب إضافة GD — متوفرة على Laravel Cloud)
    'images' => [
        'max_dimension' => 1600, // أكبر عرض/ارتفاع للصورة الأصلية بعد التصغير
        'thumb_dimension' => 600, // نسخة مصغّرة لبطاقات المنتجات
        'quality' => 82,          // جودة WebP/JPEG (82 = فرق غير ملحوظ بالعين)
    ],

    // الحد الأقصى لعدد العناصر في صفحة واحدة من أي قائمة في الـ API
    'max_per_page' => 100,
];
