<?php

namespace App\Modules\Orders\Events;

use App\Modules\Orders\Models\SubOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SubOrder $subOrder,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
