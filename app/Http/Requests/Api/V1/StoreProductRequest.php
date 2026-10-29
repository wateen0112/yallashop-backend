<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|numeric|min:0',
            'currency_id' => 'sometimes|exists:currencies,id',
            'status' => 'sometimes|string|in:' . implode(',', ProductStatus::values()),
            'variants' => 'sometimes|array|min:1',
            'variants.*.sku' => 'nullable|string|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'required|integer|min:0',
            'variants.*.attributes' => 'sometimes|array',
            'variants.*.attributes.*.attribute_id' => 'required|exists:category_attributes,id',
            'variants.*.attributes.*.option_id' => 'nullable|exists:category_attribute_options,id',
            'variants.*.attributes.*.value' => 'nullable|string|max:255',
            'variants.*.images' => 'sometimes|array',
            'variants.*.images.*.url' => 'required|string',
            'variants.*.images.*.type' => 'sometimes|string|in:image,video',
            'variants.*.images.*.sort_order' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*.url' => 'required|string',
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
            'store_id.required' => 'Store is required / المتجر مطلوب',
            'store_id.exists' => 'Store not found / المتجر غير موجود',
            'category_id.required' => 'Category is required / القسم مطلوب',
            'category_id.exists' => 'Category not found / القسم غير موجود',
            'title.required' => 'Title is required / العنوان مطلوب',
            'title.string' => 'Title must be a string / يجب أن يكون العنوان نصاً',
            'title.max' => 'Title may not exceed 255 characters / العنوان لا يتجاوز 255 حرفاً',
            'description.required' => 'Description is required / الوصف مطلوب',
            'description.string' => 'Description must be a string / يجب أن يكون الوصف نصاً',
            'base_price.required' => 'Base price is required / السعر الأساسي مطلوب',
            'base_price.numeric' => 'Base price must be a number / يجب أن يكون السعر الأساسي رقماً',
            'base_price.min' => 'Base price must be at least 0 / السعر الأساسي لا يقل عن 0',
            'status.in' => 'Status is invalid / الحالة غير صالحة',
            'variants.array' => 'Variants must be an array / يجب أن تكون المتغيرات مصفوفة',
            'variants.min' => 'At least one variant is required / مطلوب متغير واحد على الأقل',
            'variants.*.sku.required' => 'Variant SKU is required / SKU مطلوب',
            'variants.*.sku.string' => 'Variant SKU must be a string / يجب أن يكون SKU نصاً',
            'variants.*.sku.unique' => 'Variant SKU already exists / SKU موجود بالفعل',
            'variants.*.price.required' => 'Variant price is required / سعر المتغير مطلوب',
            'variants.*.price.numeric' => 'Variant price must be a number / يجب أن يكون سعر المتغير رقماً',
            'variants.*.price.min' => 'Variant price must be at least 0 / سعر المتغير لا يقل عن 0',
            'variants.*.stock_qty.required' => 'Variant stock quantity is required / كمية المخزون للمتغير مطلوبة',
            'variants.*.stock_qty.integer' => 'Variant stock must be an integer / يجب أن تكون كمية المخزون رقماً صحيحاً',
            'variants.*.stock_qty.min' => 'Variant stock must be at least 0 / كمية المخزون لا تقل عن 0',
            'images.array' => 'Images must be an array / يجب أن تكون الصور مصفوفة',
            'images.max' => 'Maximum 7 media items allowed / الحد الأقصى 7 عناصر وسائط',
            'images.*.url.required' => 'Image URL is required / رابط الصورة مطلوب',
            'images.*.url.string' => 'Image URL must be a string / يجب أن يكون رابط الصورة نصاً',
            'images.*.url.url' => 'Image URL must be a valid URL / رابط الصورة غير صحيح',
            'images.*.type.in' => 'Type must be image or video / النوع يجب أن يكون صورة أو فيديو',
            'images.*.sort_order.integer' => 'Image sort order must be an integer / ترتيب الصورة يجب أن يكون رقماً صحيحاً',
            'images.*.sort_order.min' => 'Image sort order must be at least 0 / ترتيب الصورة لا يقل عن 0',
        ];
    }
}
