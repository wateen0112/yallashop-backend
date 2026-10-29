<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Notifications\Jobs\SendPushNotification;
use App\Modules\Orders\Events\OrderPlaced;

class NotifyVendorOnOrder
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        foreach ($order->subOrders as $subOrder) {
            $shopUserId = $subOrder->store?->owner_user_id;
            if (!$shopUserId) {
                continue;
            }

            SendPushNotification::dispatch(
                $shopUserId,
                'New Order',
                "You have a new order #{$order->order_number}.",
                [
                    'type' => 'new_order',
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrder->id,
                    'status' => 'pending',
                    'role' => 'vendor',
                ]
            );
        }
    }
}