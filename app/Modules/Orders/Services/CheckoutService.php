<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Coupon;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Orders\Repositories\CartRepository;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Modules\Users\Models\Address;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected OrderRepository $orderRepository
    ) {}

    public function checkout(int $userId, array $data): Order
    {
        $cart = $this->cartRepository->getByUserWithItems($userId);

        if (!$cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty.'],
            ]);
        }

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if (!$variant) {
                throw ValidationException::withMessages([
                    'cart' => ['Product variant not found for item #' . $item->id],
                ]);
            }
            if ($variant->stock_qty < $item->qty) {
                throw ValidationException::withMessages([
                    'cart' => ["Insufficient stock for {$variant->product?->title}. Available: {$variant->stock_qty}"],
                ]);
            }
        }

        $grouped = $cart->items->groupBy(fn ($item) => $item->variant->product->store_id);

        $subtotal = 0;
        $deliveryFees = [];
        foreach ($grouped as $storeId => $items) {
            $storeSubtotal = $items->sum(fn ($i) => $i->qty * ($i->variant->price ?? 0));
            $subtotal += $storeSubtotal;
            $deliveryFees[$storeId] = $data['delivery_fees'][$storeId] ?? 0;
        }

        $discountTotal = 0;
        $coupon = null;
        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])->first();
            if (!$coupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['Invalid coupon code.'],
                ]);
            }
            if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['Coupon has expired.'],
                ]);
            }
            if ($coupon->min_order_value && $subtotal < $coupon->min_order_value) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['Minimum order value of ' . $coupon->min_order_value . ' required.'],
                ]);
            }
            if ($coupon->usage_limit && $coupon->usage_limit <= 0) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['Coupon usage limit reached.'],
                ]);
            }
            $discountTotal = $coupon->discount_type === 'percentage'
                ? round($subtotal * ($coupon->value / 100), 2)
                : min($coupon->value, $subtotal);

            if ($coupon->usage_limit) {
                $coupon->decrement('usage_limit');
            }
        }

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

        $taxTotal = $data['tax_total'] ?? 0;
        $deliveryFeeTotal = array_sum($deliveryFees);
        $paymentMethod = $data['payment_method'] ?? 'cash_on_delivery';
        $gateway = match ($paymentMethod) {
            'credit_card' => 'stripe',
            'wallet' => 'wallet',
            default => 'cod',
        };

        $order = DB::transaction(function () use (
            $userId, $cart, $grouped, $deliveryFees, $discountTotal,
            $taxTotal, $deliveryFeeTotal, $paymentMethod, $gateway, $addressSnapshot
        ) {
            $orderNumber = $this->orderRepository->generateOrderNumber();
            $subtotal = 0;

            $order = Order::create([
                'customer_id' => $userId,
                'order_number' => $orderNumber,
                'status' => OrderStatus::Pending,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => 0,
                'delivery_fee_total' => 0,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => 0,
            ]);

            foreach ($grouped as $storeId => $items) {
                $storeSubtotal = $items->sum(fn ($i) => $i->qty * ($i->variant->price ?? 0));
                $subtotal += $storeSubtotal;

                $subOrder = $order->subOrders()->create([
                    'store_id' => $storeId,
                    'status' => OrderStatus::Pending,
                    'delivery_fee' => $deliveryFees[$storeId] ?? 0,
                    'delivery_address_snapshot' => $addressSnapshot,
                ]);

                foreach ($items as $cartItem) {
                    $subOrder->items()->create([
                        'variant_id' => $cartItem->variant_id,
                        'qty' => $cartItem->qty,
                        'unit_price' => $cartItem->variant->price ?? 0,
                        'total_price' => $cartItem->qty * ($cartItem->variant->price ?? 0),
                    ]);
                }
            }

            $grandTotal = max($subtotal + $deliveryFeeTotal + $taxTotal - $discountTotal, 0);

            $order->update([
                'subtotal' => $subtotal,
                'delivery_fee_total' => $deliveryFeeTotal,
                'grand_total' => $grandTotal,
            ]);

            $order->payment()->create([
                'gateway' => $gateway,
                'status' => PaymentStatus::Pending,
                'amount' => $grandTotal,
            ]);

            $this->cartRepository->clearCart($userId);

            return $order->fresh(['subOrders.items', 'payment']);
        });

        event(new OrderPlaced($order));

        return $order;
    }
}
