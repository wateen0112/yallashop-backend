<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'options' => CategoryAttributeOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}