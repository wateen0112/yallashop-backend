<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => 'Product variant is required / النوع مطلوب',
            'variant_id.exists' => 'Product variant not found / النوع غير موجود',
            'quantity.required' => 'Quantity is required / الكمية مطلوبة',
            'quantity.integer' => 'Quantity must be an integer / يجب أن تكون الكمية رقماً صحيحاً',
            'quantity.min' => 'Quantity must be at least 1 / الكمية لا تقل عن 1',
            'quantity.max' => 'Quantity may not exceed 99 / الكمية لا تتجاوز 99',
        ];
    }
}
