<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:pending,approved,suspended',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Store name must be a string / يجب أن يكون اسم المتجر نصاً',
            'name.max' => 'Store name may not exceed 255 characters / اسم المتجر لا يتجاوز 255 حرفاً',
            'commission_rate.numeric' => 'Commission rate must be a number / نسبة العمولة يجب أن تكون رقماً',
            'commission_rate.min' => 'Commission rate must be at least 0 / نسبة العمولة لا تقل عن 0',
            'commission_rate.max' => 'Commission rate may not exceed 100 / نسبة العمولة لا تتجاوز 100',
            'status.in' => 'Status must be pending, approved, or suspended / الحالة يجب أن تكون معلقة أو مقبولة أو معلقة',
        ];
    }
}
