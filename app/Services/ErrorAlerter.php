<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * تنبيه فوري على Telegram عند حدوث خطأ في الموقع (خطأ 500 وما شابه) — مراقبة مجانية.
 * يُفعَّل عبر ERROR_ALERTS_TELEGRAM=true، ولا يُرسل نفس الخطأ أكثر من مرة كل 30 دقيقة.
 * أخطاء 404 وأخطاء التحقق لا تصل هنا (Laravel لا يسجّلها أصلًا).
 */
class ErrorAlerter
{
    private const THROTTLE_SECONDS = 1800;

    public function __construct(private TelegramClient $telegram) {}

    public function alert(Throwable $e): void
    {
        if (!config('store.error_alerts.telegram') || !$this->telegram->configured()) {
            return;
        }

        try {
            // file cache: يعمل حتى لو كانت قاعدة البيانات هي سبب الخطأ
            $key = 'error-alert:'.md5(get_class($e).'|'.$e->getFile().'|'.$e->getLine());
            if (!Cache::store('file')->add($key, true, self::THROTTLE_SECONDS)) {
                return;
            }

            $where = app()->runningInConsole() ? 'console' : request()->method().' '.request()->fullUrl();

            $this->telegram->sendMessage(
                "⚠️ خطأ في موقع ".config('app.name')."\n\n"
                .class_basename($e).': '.mb_substr($e->getMessage(), 0, 500)."\n"
                .str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine()."\n"
                .$where."\n\n"
                .'التفاصيل الكاملة في Logs على Laravel Cloud.'
            );
        } catch (Throwable) {
            // التنبيه نفسه لا يجب أن يسبب خطأ جديدًا
        }
    }
}
