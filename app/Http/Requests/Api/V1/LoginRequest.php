<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required / البريد الإلكتروني مطلوب',
            'email.email' => 'Email must be a valid email / البريد الإلكتروني غير صحيح',
            'password.required' => 'Password is required / كلمة المرور مطلوبة',
            'password.string' => 'Password must be a string / يجب أن تكون كلمة المرور نصاً',
        ];
    }
}
