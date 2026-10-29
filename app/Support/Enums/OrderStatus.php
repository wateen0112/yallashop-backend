<?php

namespace App\Support\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case WaitingForDriver = 'waiting_for_driver';
    case WithDriver = 'with_driver';
    case CourierAssigned = 'courier_assigned';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case FailedDelivery = 'failed_delivery';
    case Cancelled = 'cancelled';
}
