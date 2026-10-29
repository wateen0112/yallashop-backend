<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:customer,vendor',
            'governorate' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'area' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required / الاسم مطلوب',
            'name.string' => 'Name must be a string / يجب أن يكون الاسم نصياً',
            'name.max' => 'Name may not exceed 255 characters / الاسم لا يتجاوز 255 حرفاً',
            'phone.required' => 'Phone is required / رقم الهاتف مطلوب',
            'phone.string' => 'Phone must be a string / يجب أن يكون رقم الهاتف نصياً',
            'phone.unique' => 'This phone is already registered / رقم الهاتف مسجل بالفعل',
            'email.required' => 'Email is required / البريد الإلكتروني مطلوب',
            'email.email' => 'Email must be a valid email / البريد الإلكتروني غير صحيح',
            'email.unique' => 'This email is already registered / البريد الإلكتروني مسجل بالفعل',
            'password.required' => 'Password is required / كلمة المرور مطلوبة',
            'password.string' => 'Password must be a string / يجب أن تكون كلمة المرور نصاً',
            'password.min' => 'Password must be at least 8 characters / كلمة المرور لا تقل عن 8 أحرف',
            'password.confirmed' => 'Password confirmation does not match / تأكيد كلمة المرور غير متطابق',
            'role.in' => 'Role must be customer or vendor / الدور يجب أن يكون عميل أو بائع',
            'governorate.required' => 'Governorate is required / المحافظة مطلوبة',
            'city.required' => 'City is required / المدينة مطلوبة',
            'area.required' => 'Area is required / المنطقة مطلوبة',
        ];
    }
}
