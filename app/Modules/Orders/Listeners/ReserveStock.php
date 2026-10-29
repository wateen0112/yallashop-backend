<?php

namespace App\Modules\Orders\Listeners;

use App\Modules\Orders\Events\OrderPlaced;

class ReserveStock
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->subOrders as $subOrder) {
            foreach ($subOrder->items as $item) {
                if ($item->variant) {
                    $item->variant->decrement('stock_qty', $item->qty);
                }
            }
        }
    }
}
