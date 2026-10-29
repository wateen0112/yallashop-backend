<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Notifications\Jobs\SendPushNotification;
use App\Modules\Orders\Events\OrderPlaced;

class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        if (!$order->customer_id) {
            return;
        }

        SendPushNotification::dispatch(
            $order->customer_id,
            'Order Placed',
            "Your order #{$order->order_number} has been placed successfully.",
            [
                'type' => 'order_status',
                'order_id' => $order->id,
                'status' => 'pending',
                'role' => 'customer',
            ]
        );
    }
}