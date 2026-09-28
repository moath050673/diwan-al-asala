<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/**
 * ربط إشعارات الطلبات بـ Telegram بأمر واحد:
 *   php artisan telegram:connect <TOKEN>
 * يتحقق من التوكن، يستخرج رقم المحادثة (chat id) تلقائيًا من آخر رسالة أُرسلت للبوت،
 * يحفظ القيمتين في .env، ثم يرسل رسالة تجريبية.
 */
class TelegramConnect extends Command
{
    protected $signature = 'telegram:connect {token : التوكن الذي أعطاك إياه BotFather}';

    protected $description = 'ربط إشعارات الطلبات الجديدة ببوت Telegram';

    public function handle(): int
    {
        $token = trim($this->argument('token'));
        $api = "https://api.telegram.org/bot{$token}";

        try {
            $me = Http::timeout(15)->get("{$api}/getMe")->json();
        } catch (\Throwable $e) {
            $this->error('تعذّر الاتصال بـ Telegram. تأكد من اتصال الإنترنت ثم أعد المحاولة.');
            return self::FAILURE;
        }

        if (!($me['ok'] ?? false)) {
            $this->error('التوكن غير صحيح. انسخه كاملًا من رسالة BotFather (يشبه 123456789:AAH...).');
            return self::FAILURE;
        }
        $botName = $me['result']['username'] ?? 'البوت';
        $this->info("✔ التوكن صحيح — البوت: @{$botName}");

        $updates = Http::timeout(15)->get("{$api}/getUpdates")->json('result') ?? [];
        $chat = null;
        foreach (array_reverse($updates) as $update) {
            $chat = $update['message']['chat'] ?? $update['my_chat_member']['chat'] ?? null;
            if ($chat) break;
        }

        if (!$chat) {
            $this->warn("لم أجد أي رسالة. افتح @{$botName} في Telegram، اضغط Start (أو أرسل أي كلمة)، ثم شغّل الأمر مرة أخرى.");
            return self::FAILURE;
        }

        $this->writeEnv(['TELEGRAM_BOT_TOKEN' => $token, 'TELEGRAM_CHAT_ID' => (string) $chat['id']]);
        Artisan::call('config:clear');
        $name = trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? '')) ?: ($chat['title'] ?? '');
        $this->info("✔ تم الحفظ في .env — المحادثة: {$name} ({$chat['id']})");

        $sent = Http::timeout(15)->asForm()->post("{$api}/sendMessage", [
            'chat_id' => $chat['id'],
            'text' => "✅ تم ربط متجر ديوان الأصالة بنجاح.\nستصلك هنا رسالة مع كل طلب جديد.",
        ])->json('ok');

        $sent
            ? $this->info('✔ أُرسلت رسالة تجريبية — تفقّد Telegram.')
            : $this->warn('حُفظت الإعدادات لكن تعذّر إرسال الرسالة التجريبية.');

        return self::SUCCESS;
    }

    private function writeEnv(array $values): void
    {
        $path = app()->environmentFilePath();
        $content = file_exists($path) ? file_get_contents($path) : '';

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
