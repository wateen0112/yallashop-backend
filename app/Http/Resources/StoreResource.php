<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'description' => $this->description,
            'status' => $this->status,
            'commission_rate' => $this->commission_rate,
            'rating_avg' => $this->rating_avg,
            'city' => $this->city,
            'created_at' => $this->created_at,
        ];
    }
}
