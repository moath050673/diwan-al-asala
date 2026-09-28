<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramConnectTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        // ملف .env مؤقت — لا نلمس ملف المشروع الحقيقي
        $this->dir = sys_get_temp_dir().'/tg-'.uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir.'/.env', "APP_NAME=Test\nTELEGRAM_CHAT_ID=old\n");
        $this->app->useEnvironmentPath($this->dir);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/.env');
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_connect_saves_token_and_detected_chat_id()
    {
        Http::fake([
            '*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'diwan_bot']]),
            '*/getUpdates' => Http::response(['ok' => true, 'result' => [['message' => ['chat' => ['id' => 5551234, 'first_name' => 'Ammar']]]]]),
            '*/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->artisan('telegram:connect', ['token' => '123:ABC'])->assertSuccessful();

        $env = file_get_contents($this->dir.'/.env');
        $this->assertStringContainsString("TELEGRAM_BOT_TOKEN=123:ABC\n", $env);
        $this->assertStringContainsString("TELEGRAM_CHAT_ID=5551234\n", $env);
        $this->assertStringNotContainsString('TELEGRAM_CHAT_ID=old', $env);
    }

    public function test_invalid_token_changes_nothing()
    {
        Http::fake(['*/getMe' => Http::response(['ok' => false], 401)]);

        $this->artisan('telegram:connect', ['token' => 'bad'])->assertFailed();
        $this->assertStringNotContainsString('TELEGRAM_BOT_TOKEN', file_get_contents($this->dir.'/.env'));
    }

    public function test_asks_user_to_message_the_bot_when_no_updates()
    {
        Http::fake([
            '*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'diwan_bot']]),
            '*/getUpdates' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->artisan('telegram:connect', ['token' => '123:ABC'])->assertFailed();
    }
}
