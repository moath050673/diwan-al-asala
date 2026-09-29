<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * إرسال رسائل وملفات إلى محادثات Telegram المحددة في TELEGRAM_CHAT_ID (مفصولة بفاصلة).
 * يعمل فقط إذا كانت الإعدادات موجودة، وأي فشل يُسجَّل ولا يُوقف العملية الأصلية.
 */
class TelegramClient
{
    public function configured(): bool
    {
        return (bool) $this->token() && (bool) $this->chatIds();
    }

    public function sendMessage(string $text): bool
    {
        return $this->eachChat(fn (string $chatId) => Http::timeout(8)->asForm()
            ->post($this->endpoint('sendMessage'), [
                'chat_id' => $chatId,
                'text' => mb_substr($text, 0, 4000),
                'disable_web_page_preview' => 'true',
            ])->throw());
    }

    /** إرسال ملف (حد Telegram للبوتات: 50 ميجابايت) */
    public function sendDocument(string $path, string $filename, string $caption = ''): bool
    {
        return $this->eachChat(function (string $chatId) use ($path, $filename, $caption) {
            $stream = fopen($path, 'rb');
            try {
                Http::timeout(120)->attach('document', $stream, $filename)
                    ->post($this->endpoint('sendDocument'), ['chat_id' => $chatId, 'caption' => mb_substr($caption, 0, 1000)])
                    ->throw();
            } finally {
                if (is_resource($stream)) fclose($stream);
            }
        });
    }

    private function eachChat(callable $send): bool
    {
        if (!$this->configured()) {
            return false;
        }

        $ok = true;
        foreach ($this->chatIds() as $chatId) {
            try {
                $send($chatId);
            } catch (\Throwable $e) {
                $ok = false;
                // لا نسجّل الرابط كاملًا لأنه يحتوي التوكن
                Log::warning('Telegram request failed: '.str_replace((string) $this->token(), '***', $e->getMessage()));
            }
        }

        return $ok;
    }

    private function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.$this->token().'/'.$method;
    }

    private function token(): ?string
    {
        return config('store.telegram.bot_token') ?: null;
    }

    private function chatIds(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('store.telegram.chat_id')))));
    }
}
