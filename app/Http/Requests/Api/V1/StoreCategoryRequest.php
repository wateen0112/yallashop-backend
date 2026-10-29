<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|string',
            'image_file' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp',
            'attributes' => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required / اسم القسم مطلوب',
            'name.max' => 'Category name may not exceed 255 characters / اسم القسم لا يتجاوز 255 حرفاً',
            'slug.unique' => 'Slug already taken / الرابط مأخوذ بالفعل',
            'parent_id.exists' => 'Parent category not found / القسم الأب غير موجود',
            'sort_order.integer' => 'Sort order must be an integer / ترتيب الفرز يجب أن يكون رقماً صحيحاً',
        ];
    }
}
