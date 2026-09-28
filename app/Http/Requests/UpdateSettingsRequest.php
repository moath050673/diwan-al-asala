<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية يتحقق منها middleware role:admin
    }

    public function rules(): array
    {
        return [
            'store_name' => 'sometimes|nullable|string|max:150',
            'store_currency' => 'sometimes|nullable|string|max:50',
            'whatsapp_number' => 'sometimes|nullable|string|regex:/^[0-9+\s-]{6,20}$/',
            // url:http,https يمنع روابط javascript: التي تُحقن في href داخل الفوتر
            'facebook_url' => 'sometimes|nullable|url:http,https|max:255',
            'instagram_url' => 'sometimes|nullable|url:http,https|max:255',
            'shipping_cost_sanaa' => 'sometimes|nullable|numeric|min:0',
            'jib_enabled' => 'sometimes|boolean',
            'jib_account_name' => 'sometimes|nullable|string|max:150',
            'jib_account_number' => 'sometimes|nullable|string|max:100',
            'kareemi_enabled' => 'sometimes|boolean',
            'kareemi_account_name' => 'sometimes|nullable|string|max:150',
            'kareemi_account_number' => 'sometimes|nullable|string|max:100',
            'cod_enabled' => 'sometimes|boolean',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $unknown = array_diff(array_keys($this->all()), Setting::EDITABLE_KEYS);
                if ($unknown) {
                    $validator->errors()->add('settings', 'مفاتيح إعدادات غير معروفة: '.implode(', ', $unknown));
                }
                if (empty(array_intersect(array_keys($this->all()), Setting::EDITABLE_KEYS))) {
                    $validator->errors()->add('settings', 'لا توجد إعدادات لتحديثها');
                }
            },
        ];
    }
}
