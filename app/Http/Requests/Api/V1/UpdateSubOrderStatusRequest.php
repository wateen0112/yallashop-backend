<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:confirmed,preparing,ready_for_pickup,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required / الحالة مطلوبة',
            'status.string' => 'Status must be a string / يجب أن تكون الحالة نصاً',
            'status.in' => 'Status must be one of: confirmed, preparing, ready_for_pickup, cancelled / الحالة يجب أن تكون: مؤكد، قيد التحضير، جاهز للاستلام، ملغي',
        ];
    }
}
