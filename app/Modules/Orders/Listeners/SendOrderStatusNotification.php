<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Modules\Notifications\Jobs\SendPushNotification;

class SendOrderStatusNotification
{
    /** Statuses that are relevant to the shop (vendor). */
    private const SHOP_STATUSES = [
        'waiting_for_driver',
        'with_driver',
        'delivered',
        'cancelled',
    ];

    /** Statuses that are relevant to the assigned driver. */
    private const DRIVER_STATUSES = [
        'waiting_for_driver',
        'with_driver',
        'delivered',
        'cancelled',
    ];

    private const TITLES = [
        'confirmed' => 'Order Confirmed',
        'preparing' => 'Order is Being Prepared',
        'ready_for_pickup' => 'Order Ready for Pickup',
        'waiting_for_driver' => 'Driver Assigned',
        'with_driver' => 'Order Out for Delivery',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled',
    ];

    public function handle(OrderStatusUpdated $event): void
    {
        $subOrder = $event->subOrder;
        $order = $subOrder->order;
        $status = $event->newStatus;

        if (!$order) {
            return;
        }

        $title = self::TITLES[$status] ?? 'Order Status Updated';
        $orderNumber = $order->order_number;

        $payload = [
            'type' => 'order_status',
            'order_id' => $order->id,
            'sub_order_id' => $subOrder->id,
            'status' => $status,
        ];

        // Notify the customer for every status change.
        if ($order->customer_id) {
            $this->notify(
                $order->customer_id,
                $title,
                "Your order #{$orderNumber} status has been updated to {$status}",
                $payload,
                'customer'
            );
        }

        // Notify the shop for shop-relevant statuses.
        if (in_array($status, self::SHOP_STATUSES, true)) {
            $shopUserId = $subOrder->store?->owner_user_id;
            if ($shopUserId) {
                $this->notifyShop($shopUserId, $orderNumber, $status, $payload);
            }
        }

        // Notify the assigned driver for driver-relevant statuses.
        if (in_array($status, self::DRIVER_STATUSES, true)) {
            $driverUserId = $subOrder->courier?->user_id;
            if ($driverUserId) {
                $this->notify($driverUserId, $title, "Order #{$orderNumber} status updated to {$status}", $payload, 'driver');
            }
        }
    }

    private function notifyShop(int $userId, string $orderNumber, string $status, array $payload): void
    {
        $messages = [
            'waiting_for_driver' => "A driver has accepted order #{$orderNumber} and is on the way to pick it up",
            'with_driver' => "Order #{$orderNumber} has been picked up and is out for delivery",
            'delivered' => "Order #{$orderNumber} has been delivered",
            'cancelled' => "Order #{$orderNumber} has been cancelled",
        ];

        SendPushNotification::dispatch(
            $userId,
            self::TITLES[$status] ?? 'Order Update',
            $messages[$status] ?? "Order #{$orderNumber} status updated to {$status}",
            $payload + ['role' => 'vendor']
        );
    }

    private function notify(int $userId, string $title, string $body, array $payload, string $role): void
    {
        SendPushNotification::dispatch(
            $userId,
            $title,
            $body,
            $payload + ['role' => $role]
        );
    }
}