<?php

namespace App\Http\Resources;

use App\Support\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'store_id' => $this->store_id,
            'status' => $this->status,
            'delivery_fee' => $this->delivery_fee,
            'courier_id' => $this->courier_id,
            'pickup_code' => $this->pickup_code,
            'delivery_code' => $this->delivery_code,
            'delivery_address_snapshot' => $this->delivery_address_snapshot,
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_image' => $this->customer_image,
            'items_count' => $this->items_count,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency_snapshot,
            'store' => $this->whenLoaded('store'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];

        return $data;
    }
}