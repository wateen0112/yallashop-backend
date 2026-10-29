<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone is required / رقم الهاتف مطلوب',
            'phone.string' => 'Phone must be a string / يجب أن يكون رقم الهاتف نصاً',
        ];
    }
}
