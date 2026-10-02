<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushNotifier;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    // مفاتيح VAPID للاختبار فقط (لا تُستخدم في أي بيئة حقيقية)
    private const VAPID_PUBLIC = 'BBX1dGRls7dIDMnX5F7QCe0eIHLUKExxbqqdTmS4ief_BKV1G8a1fKNjHsSOkQckpMnT2wijaqjLYSDEuxagrNQ';
    private const VAPID_PRIVATE = 'sQtO394AMPmPDzX2vKr-qed-8o2wn3VHED7fNDhqQm0';

    private User $user;

    private function actingAsRole(string $role = 'admin'): static
    {
        $this->user = User::create(['name' => 'A', 'email' => "{$role}@t.com", 'password' => 'password123', 'role' => $role, 'status' => 'active']);
        return $this->withToken($this->user->createToken('t')->plainTextToken);
    }

    private function payload(string $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123'): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'BPubKey_-abc=', 'auth' => 'AuthTok_-x'], 'contentEncoding' => 'aes128gcm'];
    }

    public function test_staff_can_register_device_and_resubscribing_does_not_duplicate()
    {
        $this->actingAsRole('staff');

        $this->postJson('/api/push/subscriptions', $this->payload())->assertCreated();
        $this->postJson('/api/push/subscriptions', $this->payload())->assertCreated();

        $this->assertSame(1, PushSubscription::count());
        $this->assertSame($this->user->id, PushSubscription::first()->user_id);
    }

    public function test_subscription_requires_https_endpoint_and_authentication()
    {
        $this->postJson('/api/push/subscriptions', $this->payload())->assertUnauthorized();

        $this->actingAsRole()->postJson('/api/push/subscriptions', $this->payload('http://127.0.0.1/internal'))
            ->assertUnprocessable()->assertJsonValidationErrors('endpoint');
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_user_can_unsubscribe_own_device()
    {
        $this->actingAsRole();
        $this->postJson('/api/push/subscriptions', $this->payload())->assertCreated();

        $this->postJson('/api/push/subscriptions/delete', ['endpoint' => $this->payload()['endpoint']])->assertOk();

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_key_is_null_when_vapid_is_not_configured()
    {
        $this->actingAsRole()->getJson('/api/push/key')->assertOk()->assertJsonPath('data.publicKey', null);

        config(['store.web_push.public_key' => self::VAPID_PUBLIC, 'store.web_push.private_key' => self::VAPID_PRIVATE]);
        $this->getJson('/api/push/key')->assertJsonPath('data.publicKey', self::VAPID_PUBLIC);
    }

    public function test_order_is_placed_normally_without_vapid_keys()
    {
        PushSubscription::create(['user_id' => $this->actingAsRole()->user->id, 'endpoint' => 'https://push.example/x',
            'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example/x'), 'public_key' => 'k', 'auth_token' => 'a']);

        $this->placeOrder()->assertCreated(); // لا مفاتيح = لا إرسال ولا خطأ
    }

    public function test_telegram_still_arrives_when_web_push_fails()
    {
        // مثل طلب يصل أثناء النشر قبل إنشاء جدول الاشتراكات
        \Illuminate\Support\Facades\Schema::drop('push_subscriptions');
        config(['store.web_push.public_key' => self::VAPID_PUBLIC, 'store.web_push.private_key' => self::VAPID_PRIVATE,
            'store.telegram.bot_token' => 'TOKEN', 'store.telegram.chat_id' => '111']);
        \Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);

        $this->placeOrder()->assertCreated();

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), 'botTOKEN/sendMessage'));
    }

    public function test_new_order_pushes_to_every_device_and_removes_expired_ones()
    {
        // التشفير يحتاج توليد مفاتيح EC من OpenSSL (على ويندوز/XAMPP: OPENSSL_CONF)
        $deviceKey = @openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if (!$deviceKey) {
            $this->markTestSkipped('OpenSSL cannot create EC keys here (set OPENSSL_CONF on Windows).');
        }
        $details = openssl_pkey_get_details($deviceKey)['ec'];
        $p256dh = rtrim(strtr(base64_encode("\x04".$details['x'].$details['y']), '+/', '-_'), '=');

        config(['store.web_push.public_key' => self::VAPID_PUBLIC, 'store.web_push.private_key' => self::VAPID_PRIVATE]);
        $this->actingAsRole();
        foreach (['https://push.example/live', 'https://push.example/gone'] as $endpoint) {
            PushSubscription::create(['user_id' => $this->user->id, 'endpoint' => $endpoint,
                'endpoint_hash' => PushSubscription::hashEndpoint($endpoint), 'public_key' => $p256dh, 'auth_token' => 'c2VjcmV0LWF1dGgtMTY']);
        }

        $sent = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201), new Response(410)]));
        $stack->push(Middleware::history($sent));
        $this->app->instance(WebPushNotifier::class, new WebPushNotifier(new Client(['handler' => $stack])));

        $this->placeOrder()->assertCreated();

        $this->assertCount(2, $sent);
        $this->assertSame('https://push.example/live', (string) $sent[0]['request']->getUri());
        $this->assertStringStartsWith('vapid t=', $sent[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame(['https://push.example/live'], PushSubscription::pluck('endpoint')->all());
    }

    private function placeOrder()
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c']);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'P', 'slug' => 'p', 'sku' => 'S-1', 'price' => 100, 'stock_quantity' => 5]);

        return $this->postJson('/api/orders', [
            'customerName' => 'Test', 'customerPhone' => '777000111', 'city' => 'Sanaa', 'address' => 'St',
            'paymentMethod' => 'cod', 'items' => [['productId' => $product->id, 'quantity' => 1]],
        ]);
    }
}
