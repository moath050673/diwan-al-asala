<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public const PHONE_PATTERN = '/^\+?[0-9][0-9\s\-]{5,24}$/';

    public function authorize(): bool
    {
        return true; // Guest checkout
    }

    /**
     * عند الإرسال كـ FormData (لأجل رفع صورة الإيصال)، يصل حقل items كنص JSON
     * بدل مصفوفة حقيقية — نفكّه هنا قبل التحقق من الصحة.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('items'))) {
            $this->merge(['items' => json_decode($this->input('items'), true) ?? []]);
        }
    }

    public function rules(): array
    {
        return [
            'customerName' => 'required|string|max:150',
            // أرقام فقط (مع + أو مسافات أو شرطات) — يمنع إدخال نصوص عشوائية في رقم التواصل
            'customerPhone' => ['required', 'string', 'max:30', 'regex:'.self::PHONE_PATTERN],
            'customerWhatsapp' => ['nullable', 'string', 'max:30', 'regex:'.self::PHONE_PATTERN],
            'city' => 'required|string|max:100',
            'area' => 'nullable|string|max:100',
            'address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'paymentMethod' => ['required', Rule::in(array_keys(Order::PAYMENT_LABELS))],
            'transactionNumber' => 'nullable|string|max:100',
            // mimes صريحة: قاعدة image وحدها تقبل SVG في Laravel 11، وملف SVG يُخدَّم من نفس
            // النطاق يمكن أن يحتوي JavaScript (XSS) يُنفَّذ عند فتح المدير للإيصال.
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=8000,max_height=8000',
            'items' => 'required|array|min:1|max:50',
            'items.*.productId' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ];
    }
}
