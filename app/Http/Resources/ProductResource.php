<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'status' => $this->status,
            'rating_avg' => $this->rating_avg,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => [
                'url' => $img->url,
                'type' => $img->type ?? 'image',
                'sort_order' => $img->sort_order,
            ])),
            'store' => new StoreResource($this->whenLoaded('store')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
        ];
    }
}
