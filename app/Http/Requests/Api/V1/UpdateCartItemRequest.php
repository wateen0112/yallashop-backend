<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required / الكمية مطلوبة',
            'quantity.integer' => 'Quantity must be an integer / يجب أن تكون الكمية رقماً صحيحاً',
            'quantity.min' => 'Quantity must be at least 1 / الكمية لا تقل عن 1',
            'quantity.max' => 'Quantity may not exceed 99 / الكمية لا تتجاوز 99',
        ];
    }
}
