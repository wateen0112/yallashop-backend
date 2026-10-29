<?php

namespace App\Support\StateMachine;

use App\Support\Enums\OrderStatus;

class OrderStateMachine
{
    protected array $transitions = [
        OrderStatus::Pending->value => [
            OrderStatus::Confirmed,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Confirmed->value => [
            OrderStatus::Preparing,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Preparing->value => [
            OrderStatus::ReadyForPickup,
            OrderStatus::Cancelled,
        ],
        OrderStatus::ReadyForPickup->value => [
            OrderStatus::WaitingForDriver,
            OrderStatus::CourierAssigned,
            OrderStatus::Cancelled,
        ],
        OrderStatus::WaitingForDriver->value => [
            OrderStatus::WithDriver,
        ],
        OrderStatus::WithDriver->value => [
            OrderStatus::Delivered,
        ],
        OrderStatus::CourierAssigned->value => [
            OrderStatus::PickedUp,
            OrderStatus::Cancelled,
        ],
        OrderStatus::PickedUp->value => [
            OrderStatus::OutForDelivery,
        ],
        OrderStatus::OutForDelivery->value => [
            OrderStatus::Delivered,
            OrderStatus::FailedDelivery,
        ],
        OrderStatus::FailedDelivery->value => [
            OrderStatus::OutForDelivery,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Delivered->value => [],
        OrderStatus::Cancelled->value => [],
    ];

    public function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to, $this->transitions[$from->value] ?? [], true);
    }

    public function allowedTransitions(OrderStatus $from): array
    {
        return $this->transitions[$from->value] ?? [];
    }

    public function transition(OrderStatus $from, OrderStatus $to): bool
    {
        return $this->canTransition($from, $to);
    }
}
