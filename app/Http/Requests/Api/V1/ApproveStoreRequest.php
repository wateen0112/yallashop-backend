<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'commission_rate.numeric' => 'Commission rate must be a number / يجب أن تكون نسبة العمولة رقماً',
            'commission_rate.min' => 'Commission rate must be at least 0 / نسبة العمولة لا تقل عن 0',
            'commission_rate.max' => 'Commission rate must not exceed 100 / نسبة العمولة لا تتجاوز 100',
        ];
    }
}
