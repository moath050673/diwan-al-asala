<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Minishlink\WebPush\VAPID;

/**
 * توليد مفاتيح VAPID لإشعارات الطلبات الحقيقية (Web Push):
 *   php artisan push:vapid
 * محليًا: تُحفظ في .env مباشرة. على Laravel Cloud: انسخ القيمتين إلى متغيرات البيئة (Environment).
 * تغيير المفاتيح لاحقًا يُبطل كل الأجهزة المسجلة (يجب تفعيل الإشعارات عليها من جديد).
 */
class PushVapid extends Command
{
    protected $signature = 'push:vapid {--show : اعرض المفاتيح فقط بدون الحفظ في .env} {--force : استبدل مفاتيح موجودة}';

    protected $description = 'توليد مفاتيح إشعارات الطلبات (Web Push / VAPID)';

    public function handle(): int
    {
        if (config('store.web_push.public_key') && !$this->option('force') && !$this->option('show')) {
            $this->warn('المفاتيح موجودة بالفعل. استبدالها يوقف الإشعارات على كل الأجهزة المسجلة — استخدم --force إن كنت متأكدًا.');
            return self::FAILURE;
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            // ويندوز/XAMPP: OpenSSL يحتاج ملف إعداداته — OPENSSL_CONF=C:\xampp\php\extras\ssl\openssl.cnf
            $this->error('تعذّر توليد المفاتيح: '.$e->getMessage());
            $this->line('على ويندوز (XAMPP) عرّف متغير البيئة OPENSSL_CONF=C:\xampp\php\extras\ssl\openssl.cnf ثم أعد المحاولة.');
            return self::FAILURE;
        }

        $values = ['VAPID_PUBLIC_KEY' => $keys['publicKey'], 'VAPID_PRIVATE_KEY' => $keys['privateKey']];

        $envPath = app()->environmentFilePath();
        if ($this->option('show') || !is_writable($envPath)) {
            $this->info('أضف هذين المتغيرين إلى متغيرات البيئة (Laravel Cloud ← Environment ← Variables) ثم أعد النشر:');
            foreach ($values as $key => $value) {
                $this->line("{$key}={$value}");
            }
            return self::SUCCESS;
        }

        $this->writeEnv($envPath, $values);
        Artisan::call('config:clear');
        $this->info('✔ حُفظت المفاتيح في .env — افتح لوحة التحكم واضغط "تفعيل إشعارات الطلبات" على جوالك.');

        return self::SUCCESS;
    }

    private function writeEnv(string $path, array $values): void
    {
        $content = file_get_contents($path);

        foreach ($values as $key => $value) {
            $line = "{$key}={$value}";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, $line, $content)
                : rtrim($content, "\r\n")."\n{$line}\n";
        }

        file_put_contents($path, $content);
    }
}
