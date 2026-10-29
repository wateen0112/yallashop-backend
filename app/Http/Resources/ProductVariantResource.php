<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'price' => $this->price,
            'stock_qty' => $this->stock_qty,
            'attributes' => ProductVariantAttributeResource::collection($this->whenLoaded('attributes')),
            'images' => ProductVariantImageResource::collection($this->whenLoaded('images')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
