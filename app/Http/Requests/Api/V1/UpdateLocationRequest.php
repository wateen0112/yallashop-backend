<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required / خط العرض مطلوب',
            'lat.numeric' => 'Latitude must be a number / يجب أن يكون خط العرض رقماً',
            'lat.between' => 'Latitude must be between -90 and 90 / خط العرض يجب أن يكون بين -90 و 90',
            'lng.required' => 'Longitude is required / خط الطول مطلوب',
            'lng.numeric' => 'Longitude must be a number / يجب أن يكون خط الطول رقماً',
            'lng.between' => 'Longitude must be between -180 and 180 / خط الطول يجب أن يكون بين -180 و 180',
        ];
    }
}
