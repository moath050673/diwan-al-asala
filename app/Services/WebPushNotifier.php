<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PushSubscription;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Http\Client\ClientInterface;

/**
 * إشعارات حقيقية على جوال/كمبيوتر المدير (Web Push) — تظهر في شريط الإشعارات وشاشة القفل
 * مثل إشعارات واتساب وفيسبوك، حتى لو كانت لوحة التحكم والمتصفح مغلقين.
 * المتصفح (Chrome/Edge/Firefox/Safari) يستقبلها عبر خدمة الإشعارات الخاصة به، والخادم يوقّع
 * كل رسالة بمفاتيح VAPID (php artisan push:vapid). بدون المفاتيح لا يُرسل شيء ولا يحدث خطأ.
 */
class WebPushNotifier
{
    public function __construct(private ?ClientInterface $client = null) {}

    public function enabled(): bool
    {
        return (bool) (config('store.web_push.public_key') && config('store.web_push.private_key'));
    }

    public function notifyNewOrder(Order $order): void
    {
        $method = Order::PAYMENT_LABELS[$order->payment_method] ?? $order->payment_method;

        $this->send(PushSubscription::query()->get(), [
            'title' => "🔔 طلب جديد #{$order->order_number}",
            'body' => "{$order->customer_name} — ".number_format((float) $order->total)." ريال\n{$method}"
                .($order->delivery ? ' · توصيل' : ' · استلام من المتجر'),
            'url' => '/admin/orders?open='.$order->id,
            'tag' => 'order-'.$order->id, // نفس وسم تنبيه لوحة التحكم المفتوحة — لا يظهر الإشعار مرتين
        ]);
    }

    /** إشعار تجريبي لأجهزة مستخدم واحد — زر "تجربة الإشعار" في لوحة التحكم */
    public function sendTest(int $userId): int
    {
        return $this->send(PushSubscription::where('user_id', $userId)->get(), [
            'title' => '✅ الإشعارات تعمل',
            'body' => 'ستصلك رسالة هنا مع كل طلب جديد في متجر ديوان الأصالة.',
            'url' => '/admin/orders',
            'tag' => 'test',
        ]);
    }

    /**
     * يرسل لكل الأجهزة ويحذف الاشتراكات المنتهية (المستخدم ألغى الإذن أو مسح بيانات المتصفح).
     * أي فشل يُسجَّل فقط — لا يجب أن يؤثر على الطلب أو بقية القنوات.
     *
     * @return int عدد الأجهزة التي وصلها الإشعار
     */
    private function send(Collection $subscriptions, array $payload): int
    {
        if (!$this->enabled() || $subscriptions->isEmpty()) {
            return 0;
        }

        $delivered = 0;

        try {
            $webPush = new WebPush(
                ['VAPID' => [
                    'subject' => $this->subject(),
                    'publicKey' => config('store.web_push.public_key'),
                    'privateKey' => config('store.web_push.private_key'),
                ]],
                ['TTL' => 86400, 'urgency' => 'high'], // يبقى الإشعار يومًا كاملًا إن كان الجوال مغلقًا
                $this->client ?? new Client(['timeout' => 10, 'connect_timeout' => 5]),
            );

            $json = json_encode($payload + ['icon' => '/img/icon-192.png', 'badge' => '/img/notify-badge.png'], JSON_UNESCAPED_UNICODE);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]), $json);
            }

            $expired = [];
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $delivered++;
                } elseif ($report->isSubscriptionExpired()) {
                    $expired[] = PushSubscription::hashEndpoint($report->getEndpoint());
                } else {
                    Log::warning('تعذّر إرسال إشعار Web Push: '.$report->getReason());
                }
            }

            if ($expired) {
                PushSubscription::whereIn('endpoint_hash', $expired)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('تعذّر إرسال إشعارات Web Push: '.$e->getMessage());
        }

        return $delivered;
    }

    /** وسيلة تواصل مع صاحب الموقع تطلبها خدمات الإشعارات (Apple/Google) — بريد أو رابط */
    private function subject(): string
    {
        if ($subject = config('store.web_push.subject')) {
            return $subject;
        }

        $email = config('store.owner_email');

        return $email ? "mailto:{$email}" : rtrim((string) config('app.url'), '/');
    }
}
