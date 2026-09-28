<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * إنشاء منتج (POST) أو تعديله جزئيًا (PUT /products/{id}).
 */
class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية يتحقق منها middleware role:admin,staff
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        $isUpdate = $productId !== null;
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'categoryId' => [$required, 'integer', 'exists:categories,id'],
            'name' => [$required, 'string', 'max:200'],
            // اختياري: يُولَّد تلقائيًا عند تركه فارغًا
            'sku' => ['sometimes', 'nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => [$required, 'numeric', 'min:0'],
            'oldPrice' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'stockQuantity' => [$required, 'integer', 'min:0'],
            'weight' => ['sometimes', 'nullable', 'string', 'max:50'],
            'size' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive'],
            'featured' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /** يحوّل أسماء الحقول القادمة من الواجهة (camelCase) إلى أعمدة قاعدة البيانات */
    public function toAttributes(): array
    {
        $map = ['categoryId' => 'category_id', 'oldPrice' => 'old_price', 'stockQuantity' => 'stock_quantity'];
        $attributes = [];
        foreach ($this->validated() as $key => $value) {
            if ($key === 'sku' && ($value === null || $value === '')) continue; // لا نمسح SKU موجودًا
            $attributes[$map[$key] ?? $key] = $value;
        }

        return $attributes;
    }
}
