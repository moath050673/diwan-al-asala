<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use App\Services\WebPushNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * تسجيل أجهزة المدير/الموظفين لاستقبال إشعارات الطلبات (Web Push).
 */
class PushSubscriptionController extends Controller
{
    /** المفتاح العام الذي يحتاجه المتصفح للاشتراك (null = الإشعارات غير مفعّلة على الخادم) */
    public function key(WebPushNotifier $webPush)
    {
        return response()->json(['success' => true, 'data' => [
            'publicKey' => $webPush->enabled() ? config('store.web_push.public_key') : null,
        ]]);
    }

    public function store(StorePushSubscriptionRequest $request)
    {
        $data = $request->validated();

        // نفس الجهاز قد يشترك مرة أخرى (أو بحساب آخر) — نحدّث السجل بدل تكراره
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ],
        );

        return response()->json(['success' => true, 'message' => 'تم تفعيل الإشعارات على هذا الجهاز'], 201);
    }

    public function destroy(Request $request)
    {
        $endpoint = (string) $request->validate(['endpoint' => ['required', 'string', 'max:2000']])['endpoint'];

        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'تم إيقاف الإشعارات على هذا الجهاز']);
    }

    public function test(Request $request, WebPushNotifier $webPush)
    {
        if (!$webPush->enabled()) {
            return response()->json(['success' => false, 'message' => 'الإشعارات غير مفعّلة على الخادم (مفاتيح VAPID غير مضبوطة)'], 422);
        }

        $sent = $webPush->sendTest($request->user()->id);

        return $sent
            ? response()->json(['success' => true, 'message' => "أُرسل إشعار تجريبي إلى {$sent} جهاز"])
            : response()->json(['success' => false, 'message' => 'لا يوجد جهاز مسجّل — فعّل الإشعارات أولًا'], 422);
    }
}
