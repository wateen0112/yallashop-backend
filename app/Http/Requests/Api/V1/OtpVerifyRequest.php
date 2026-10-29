<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone is required / رقم الهاتف مطلوب',
            'phone.string' => 'Phone must be a string / يجب أن يكون رقم الهاتف نصاً',
            'otp.required' => 'OTP is required / رمز التحقق مطلوب',
            'otp.string' => 'OTP must be a string / يجب أن يكون رمز التحقق نصاً',
            'otp.size' => 'OTP must be exactly 6 characters / رمز التحقق يجب أن يكون 6 أحرف بالضبط',
        ];
    }
}
