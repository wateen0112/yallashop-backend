<?php

namespace App\Modules\Vendors\Repositories;

use App\Modules\Orders\Models\SubOrder;
use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Support\Enums\OrderStatus;
use App\Support\StateMachine\OrderStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorOrderRepository
{
    public function getByStore(int $storeId): LengthAwarePaginator
    {
        return SubOrder::with(['items.variant.images', 'items.variant.attributes.attribute', 'items.variant.attributes.option', 'items.variant.product.images', 'order.customer', 'courier'])
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function updateStatus(?int $storeId, int $subOrderId, string $status): ?SubOrder
    {
        $query = SubOrder::where('id', $subOrderId);
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        $subOrder = $query->first();
        if (!$subOrder) {
            return null;
        }

        $machine = new OrderStateMachine();
        $currentStatus = $subOrder->status;

        if (!$currentStatus instanceof OrderStatus) {
            $currentStatus = OrderStatus::tryFrom($currentStatus);
        }

        $targetStatus = OrderStatus::tryFrom($status);
        if (!$targetStatus) {
            return null;
        }

        if (!$machine->canTransition($currentStatus, $targetStatus)) {
            return null;
        }

        $updateData = ['status' => $targetStatus];

        // Generate pickup_code when marking ready_for_pickup
        if ($targetStatus === OrderStatus::ReadyForPickup) {
            $updateData['pickup_code'] = SubOrder::generateVerificationCode();
        }

        $subOrder->update($updateData);

        OrderStatusUpdated::dispatch($subOrder, $currentStatus->value, $targetStatus->value);

        return $subOrder->fresh(['items.variant.images', 'items.variant.attributes.attribute', 'items.variant.attributes.option', 'items.variant.product.images', 'order.customer', 'courier']);
    }

    public function getPendingCount(int $storeId): int
    {
        return SubOrder::where('store_id', $storeId)
            ->where('status', OrderStatus::Pending)
            ->count();
    }
}