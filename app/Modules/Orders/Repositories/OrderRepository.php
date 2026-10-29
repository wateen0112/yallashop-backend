<?php

namespace App\Modules\Orders\Repositories;

use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Events\OrderStatusUpdated;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Payments\Models\Payment;
use App\Modules\Users\Models\Address;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\StateMachine\OrderStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function getByUser(int $userId): LengthAwarePaginator
    {
        return Order::with(['subOrders.store', 'subOrders.items.variant.images', 'subOrders.items.variant.attributes.attribute', 'subOrders.items.variant.attributes.option', 'subOrders.items.variant.product.images'])
            ->where('customer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function findWithSubOrders(int $userId, int $orderId): ?Order
    {
        return Order::with(['subOrders.store', 'subOrders.items.variant.images', 'subOrders.items.variant.attributes.attribute', 'subOrders.items.variant.attributes.option', 'subOrders.items.variant.product.images', 'subOrders.items.variant.product.currency', 'payment'])
            ->where('id', $orderId)
            ->where('customer_id', $userId)
            ->first();
    }

    public function placeOrder(int $userId, array $data): Order
    {
        return DB::transaction(function () use ($userId, $data) {
            $cartRepo = app(CartRepository::class);
            $cart = $cartRepo->getByUserWithItems($userId);

            $grouped = $cart->items->groupBy(function ($item) {
                return $item->variant->product->store_id;
            });

            $orderNumber = $this->generateOrderNumber();

            // Snapshot the delivery address
            $addressSnapshot = null;
            if (!empty($data['address_id'])) {
                $address = Address::where('id', $data['address_id'])
                    ->where('user_id', $userId)
                    ->first();
                if ($address) {
                    $addressSnapshot = [
                        'label' => $address->label,
                        'lat' => $address->lat,
                        'lng' => $address->lng,
                        'city' => $address->city,
                        'area' => $address->area,
                        'full_address' => $address->full_address,
                    ];
                }
            }

            $subtotal = 0;
            $deliveryFeeTotal = 0;

            $order = Order::create([
                'customer_id' => $userId,
                'order_number' => $orderNumber,
                'status' => OrderStatus::Pending,
                'payment_method' => $data['payment_method'],
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => 0,
                'delivery_fee_total' => 0,
                'discount_total' => $data['discount_total'] ?? 0,
                'tax_total' => $data['tax_total'] ?? 0,
                'grand_total' => 0,
            ]);

            foreach ($grouped as $storeId => $items) {
                $storeSubtotal = $items->sum(function ($item) {
                    return $item->qty * ($item->variant->price ?? 0);
                });

                $deliveryFee = $data['delivery_fees'][$storeId] ?? 0;

                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'store_id' => $storeId,
                    'status' => OrderStatus::Pending,
                    'delivery_fee' => $deliveryFee,
                    'delivery_address_snapshot' => $addressSnapshot,
                ]);

                foreach ($items as $cartItem) {
                    OrderItem::create([
                        'sub_order_id' => $subOrder->id,
                        'variant_id' => $cartItem->variant_id,
                        'qty' => $cartItem->qty,
                        'unit_price' => $cartItem->variant->price ?? 0,
                        'total_price' => $cartItem->qty * ($cartItem->variant->price ?? 0),
                    ]);
                }

                $subtotal += $storeSubtotal;
                $deliveryFeeTotal += $deliveryFee;
            }

            $grandTotal = $subtotal + $deliveryFeeTotal + ($data['tax_total'] ?? 0) - ($data['discount_total'] ?? 0);

            $order->update([
                'subtotal' => $subtotal,
                'delivery_fee_total' => $deliveryFeeTotal,
                'grand_total' => max($grandTotal, 0),
            ]);

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $data['gateway'] ?? 'cod',
                'transaction_ref' => null,
                'status' => PaymentStatus::Pending,
                'amount' => max($grandTotal, 0),
            ]);

            $cartRepo->clearCart($userId);

            event(new OrderPlaced($order));

            return $order->fresh(['subOrders.items', 'payment']);
        });
    }

    public function cancel(int $userId, int $orderId): bool
    {
        $order = Order::where('id', $orderId)
            ->where('customer_id', $userId)
            ->first();

        if (!$order) {
            return false;
        }

        $machine = new OrderStateMachine();
        $currentStatus = $order->status;

        if (!$currentStatus instanceof OrderStatus) {
            $currentStatus = OrderStatus::tryFrom($currentStatus);
        }

        if ($machine->canTransition($currentStatus, OrderStatus::Cancelled)) {
            $order->update(['status' => OrderStatus::Cancelled]);

            // Mark sub-orders cancelled and notify the affected parties.
            foreach ($order->subOrders as $subOrder) {
                $oldStatus = $subOrder->status;
                $subOrder->update(['status' => OrderStatus::Cancelled]);
                OrderStatusUpdated::dispatch($subOrder, $oldStatus, OrderStatus::Cancelled->value);
            }

            return true;
        }

        return false;
    }

    public function generateOrderNumber(): string
    {
        $prefix = 'ORD-';

        do {
            $number = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}