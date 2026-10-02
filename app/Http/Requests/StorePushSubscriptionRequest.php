<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * اشتراك متصفح في إشعارات الطلبات — نفس الكائن الذي يُرجعه PushSubscription.toJSON() في المتصفح.
 */
class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية يتحقق منها middleware المسار (role:admin,staff)
    }

    public function rules(): array
    {
        return [
            // خدمات الإشعارات الحقيقية تعمل عبر HTTPS فقط — يمنع تخزين روابط داخلية يرسل لها الخادم
            'endpoint' => ['required', 'string', 'max:2000', 'url:https'],
            'keys.p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-+\/=]+$/'],
            'keys.auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-+\/=]+$/'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ];
    }
}
