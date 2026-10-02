<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * إشعار صاحب المتجر بالطلبات الجديدة خارج لوحة التحكم:
 * - Web Push: إشعار حقيقي على جوال/كمبيوتر المدير (مثل واتساب) حتى لو كانت لوحة التحكم مغلقة
 * - Telegram: رسالة فورية تصل للجوال والكمبيوتر حتى لو كانت لوحة التحكم مغلقة
 * - البريد الإلكتروني
 * كل قناة تعمل فقط إذا كانت إعداداتها موجودة في .env، وأي فشل يُسجَّل ولا يوقف الطلب.
 */
class NewOrderNotifier
{
    public function __construct(private TelegramClient $telegram, private WebPushNotifier $webPush) {}

    public function notify(Order $order): void
    {
        $order->loadMissing('items');
        $message = $this->message($order);

        // كل قناة معزولة: فشل إحداها (جدول ناقص، خدمة متوقفة...) لا يمنع وصول البقية
        $this->safely('Telegram', fn () => $this->telegram->sendMessage($message));
        $this->safely('Web Push', fn () => $this->webPush->notifyNewOrder($order));
        $this->safely('Email', fn () => $this->sendEmail($order, $message));
    }

    private function safely(string $channel, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("تعذّر إرسال إشعار الطلب عبر {$channel}: ".$e->getMessage());
        }
    }

    private function message(Order $order): string
    {
        $items = $order->items->map(fn ($i) => "• {$i->product_name} × {$i->quantity}")->implode("\n");
        $total = number_format((float) $order->total);
        $method = Order::PAYMENT_LABELS[$order->payment_method] ?? $order->payment_method;

        return "🔔 طلب جديد #{$order->order_number}\n\n"
            ."👤 {$order->customer_name}\n"
            ."📞 {$order->customer_phone}\n"
            .($order->delivery
                ? "🚚 توصيل: {$order->customer_address}\n"
                    .($order->map_url ? "📍 الموقع على الخريطة: {$order->map_url}\n" : '')."\n"
                : "🏪 استلام من المتجر (بدون توصيل)\n\n")
            ."{$items}\n\n"
            ."💰 الإجمالي: {$total} ريال\n"
            ."💳 {$method}\n\n"
            .rtrim((string) config('app.url'), '/').'/admin/orders';
    }

    private function sendEmail(Order $order, string $message): void
    {
        $ownerEmail = config('store.owner_email');
        if (!$ownerEmail) {
            return;
        }

        try {
            Mail::raw($message, fn ($msg) => $msg->to($ownerEmail)->subject("🔔 طلب جديد #{$order->order_number} — ديوان الأصالة"));
        } catch (\Throwable $e) {
            Log::warning('تعذّر إرسال إشعار البريد: '.$e->getMessage());
        }
    }
}
