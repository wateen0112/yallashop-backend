<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->attribute_id,
            'option_id' => $this->option_id,
            'value' => $this->value,
            'attribute' => new CategoryAttributeResource($this->whenLoaded('attribute')),
            'option' => new CategoryAttributeOptionResource($this->whenLoaded('option')),
        ];
    }
}
