<?php

namespace App\Modules\Delivery\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $courierId,
        public float $lat,
        public float $lng
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('courier-location.' . $this->courierId);
    }
}
