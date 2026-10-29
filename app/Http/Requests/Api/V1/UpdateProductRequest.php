<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'base_price' => 'sometimes|numeric|min:0',
            'currency_id' => 'sometimes|exists:currencies,id',
            'status' => 'sometimes|string|in:' . implode(',', ProductStatus::values()),
            'variants' => 'sometimes|array',
            'variants.*.sku' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'required|integer|min:0',
            'variants.*.attributes' => 'sometimes|array',
            'variants.*.attributes.*.attribute_id' => 'required|exists:category_attributes,id',
            'variants.*.attributes.*.option_id' => 'nullable|exists:category_attribute_options,id',
            'variants.*.attributes.*.value' => 'nullable|string|max:255',
            'variants.*.images' => 'sometimes|array',
            'variants.*.images.*.url' => 'required|string|url',
            'variants.*.images.*.type' => 'sometimes|string|in:image,video',
            'variants.*.images.*.sort_order' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*.url' => 'required|string|url',
            'images.*.type' => 'sometimes|string|in:image,video',
            'images.*.sort_order' => 'sometimes|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Product images - only 1 video allowed
            if ($this->has('images') && is_array($this->input('images'))) {
                $videoCount = collect($this->input('images'))->where('type', 'video')->count();
                if ($videoCount > 1) {
                    $validator->errors()->add('images', 'Only 1 video is allowed / فيديو واحد فقط مسموح');
                }
            }

            // Variant images - only 1 video per variant allowed
            if ($this->has('variants') && is_array($this->input('variants'))) {
                foreach ($this->input('variants') as $index => $variant) {
                    if (isset($variant['images']) && is_array($variant['images'])) {
                        $videoCount = collect($variant['images'])->where('type', 'video')->count();
                        if ($videoCount > 1) {
                            $validator->errors()->add("variants.{$index}.images", 'Only 1 video is allowed per variant / فيديو واحد فقط مسموح لكل متغير');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Category not found / القسم غير موجود',
            'title.string' => 'Title must be a string / يجب أن يكون العنوان نصاً',
            'title.max' => 'Title may not exceed 255 characters / العنوان لا يتجاوز 255 حرفاً',
            'description.string' => 'Description must be a string / يجب أن يكون الوصف نصاً',
            'base_price.numeric' => 'Base price must be a number / يجب أن يكون السعر الأساسي رقماً',
            'base_price.min' => 'Base price must be at least 0 / السعر الأساسي لا يقل عن 0',
            'status.in' => 'Status is invalid / الحالة غير صالحة',
            'images.max' => 'Maximum 7 media items allowed / الحد الأقصى 7 عناصر وسائط',
            'images.*.url.required' => 'Media URL is required / رابط الوسائط مطلوب',
            'images.*.url.url' => 'Media URL must be a valid URL / رابط الوسائط غير صحيح',
            'images.*.type.in' => 'Type must be image or video / النوع يجب أن يكون صورة أو فيديو',
        ];
    }
}
