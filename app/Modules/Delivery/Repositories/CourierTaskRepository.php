<?php

namespace App\Modules\Delivery\Repositories;

use App\Modules\Delivery\Models\Courier;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Support\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CourierTaskRepository
{
    public function getAvailableTasks(int $userId): Collection
    {
        $courier = Courier::where('user_id', $userId)->where('status', 'online')->first();
        if (!$courier) {
            return new Collection();
        }

        return SubOrder::with(['store', 'order.customer', 'items.variant.images', 'items.variant.attributes.attribute', 'items.variant.attributes.option', 'items.variant.product.images', 'items.variant.product.currency'])
            ->where('status', OrderStatus::ReadyForPickup)
            ->whereNull('courier_id')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function acceptTask(int $userId, int $subOrderId): bool
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return false;
        }

        return DB::transaction(function () use ($courier, $subOrderId) {
            $subOrder = SubOrder::where('id', $subOrderId)
                ->where('status', OrderStatus::ReadyForPickup)
                ->whereNull('courier_id')
                ->lockForUpdate()
                ->first();

            if (!$subOrder) {
                return false;
            }

            $updated = $subOrder->update([
                'courier_id' => $courier->id,
                'status' => OrderStatus::WaitingForDriver,
            ]);

            if ($updated) {
                OrderStatusUpdated::dispatch($subOrder, OrderStatus::ReadyForPickup->value, OrderStatus::WaitingForDriver->value);
            }

            return $updated;
        });
    }

    public function rejectTask(int $userId, int $subOrderId): bool
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return false;
        }

        $subOrder = SubOrder::where('id', $subOrderId)
            ->where('courier_id', $courier->id)
            ->where('status', OrderStatus::WaitingForDriver)
            ->first();

        if (!$subOrder) {
            return false;
        }

        $updated = $subOrder->update([
            'courier_id' => null,
            'status' => OrderStatus::ReadyForPickup,
        ]);

        if ($updated) {
            OrderStatusUpdated::dispatch($subOrder, OrderStatus::WaitingForDriver->value, OrderStatus::ReadyForPickup->value);
        }

        return $updated;
    }

    public function verifyPickupCode(int $userId, int $subOrderId, string $pickupCode): bool
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return false;
        }

        $subOrder = SubOrder::where('id', $subOrderId)
            ->where('courier_id', $courier->id)
            ->where('status', OrderStatus::WaitingForDriver)
            ->first();

        if (!$subOrder) {
            return false;
        }

        if ($subOrder->pickup_code !== $pickupCode) {
            return false;
        }

        $updated = $subOrder->update([
            'status' => OrderStatus::WithDriver,
            'delivery_code' => SubOrder::generateVerificationCode(),
            'picked_up_at' => now(),
        ]);

        if ($updated) {
            OrderStatusUpdated::dispatch($subOrder, OrderStatus::WaitingForDriver->value, OrderStatus::WithDriver->value);
        }

        return $updated;
    }

    public function verifyDeliveryCode(int $userId, int $subOrderId, string $deliveryCode): bool
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return false;
        }

        $subOrder = SubOrder::where('id', $subOrderId)
            ->where('courier_id', $courier->id)
            ->where('status', OrderStatus::WithDriver)
            ->first();

        if (!$subOrder) {
            return false;
        }

        if ($subOrder->delivery_code !== $deliveryCode) {
            return false;
        }

        $updated = $subOrder->update([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);

        if ($updated) {
            OrderStatusUpdated::dispatch($subOrder, OrderStatus::WithDriver->value, OrderStatus::Delivered->value);
        }

        return $updated;
    }

    public function updateLocation(int $userId, array $location): void
    {
        Courier::where('user_id', $userId)->update([
            'current_lat' => $location['lat'],
            'current_lng' => $location['lng'],
        ]);
    }

    public function getActiveTasks(int $userId): Collection
    {
        $courier = Courier::where('user_id', $userId)->first();
        if (!$courier) {
            return new Collection();
        }

        return SubOrder::with(['store', 'order.customer', 'items.variant.images', 'items.variant.attributes.attribute', 'items.variant.attributes.option', 'items.variant.product.images', 'items.variant.product.currency'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', [
                OrderStatus::WaitingForDriver,
                OrderStatus::WithDriver,
                OrderStatus::Delivered,
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
