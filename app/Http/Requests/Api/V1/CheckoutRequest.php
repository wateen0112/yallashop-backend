<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string|in:cash_on_delivery,credit_card,wallet',
            'coupon_code' => 'nullable|string|exists:coupons,code',
            'delivery_instructions' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'Address is required / العنوان مطلوب',
            'address_id.exists' => 'Address not found / العنوان غير موجود',
            'payment_method.required' => 'Payment method is required / طريقة الدفع مطلوبة',
            'payment_method.string' => 'Payment method must be a string / يجب أن تكون طريقة الدفع نصاً',
            'payment_method.in' => 'Payment method must be cash_on_delivery, credit_card or wallet / طريقة الدفع يجب أن تكون دفع عند الاستلام أو بطاقة ائتمان أو محفظة',
            'coupon_code.string' => 'Coupon code must be a string / يجب أن يكون رمز الكوبون نصاً',
            'coupon_code.exists' => 'Coupon code is invalid / رمز الكوبون غير صحيح',
            'delivery_instructions.string' => 'Delivery instructions must be a string / يجب أن تكون تعليمات التوصيل نصاً',
            'delivery_instructions.max' => 'Delivery instructions may not exceed 500 characters / تعليمات التوصيل لا تتجاوز 500 حرف',
        ];
    }
}
