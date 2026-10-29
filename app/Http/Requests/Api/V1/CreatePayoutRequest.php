<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreatePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payee_type' => 'required|string|in:vendor,courier',
            'payee_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ];
    }

    public function messages(): array
    {
        return [
            'payee_type.required' => 'Payee type is required / نوع المستفيد مطلوب',
            'payee_type.string' => 'Payee type must be a string / يجب أن يكون نوع المستفيد نصاً',
            'payee_type.in' => 'Payee type must be vendor or courier / نوع المستفيد يجب أن يكون بائع أو مندوب',
            'payee_id.required' => 'Payee ID is required / معرف المستفيد مطلوب',
            'payee_id.integer' => 'Payee ID must be an integer / يجب أن يكون معرف المستفيد رقماً صحيحاً',
            'payee_id.exists' => 'Payee not found / المستفيد غير موجود',
            'amount.required' => 'Amount is required / المبلغ مطلوب',
            'amount.numeric' => 'Amount must be a number / يجب أن يكون المبلغ رقماً',
            'amount.min' => 'Amount must be at least 0.01 / المبلغ لا يقل عن 0.01',
            'period_start.required' => 'Period start is required / بداية الفترة مطلوبة',
            'period_start.date' => 'Period start must be a valid date / بداية الفترة يجب أن تكون تاريخاً صحيحاً',
            'period_end.required' => 'Period end is required / نهاية الفترة مطلوبة',
            'period_end.date' => 'Period end must be a valid date / نهاية الفترة يجب أن تكون تاريخاً صحيحاً',
            'period_end.after_or_equal' => 'Period end must be after or equal to period start / نهاية الفترة يجب أن تكون بعد أو تساوي بداية الفترة',
        ];
    }
}
