<?php

namespace App\Modules\Orders\Repositories;

use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Models\CartItem;
use Illuminate\Support\Collection;

class CartRepository
{
    public function getByUserWithItems(int $userId): ?Cart
    {
        return Cart::with(['items.variant.product.store', 'items.variant.product.currency', 'items.variant.attributes.attribute', 'items.variant.attributes.option', 'items.variant.images'])
            ->where('user_id', $userId)
            ->first();
    }

    public function addItem(int $userId, array $data): CartItem
    {
        $cart = Cart::firstOrCreate(['user_id' => $userId]);

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $data['variant_id'])
            ->first();

        if ($existing) {
            $existing->increment('qty', $data['qty'] ?? 1);
            return $existing->fresh(['variant.product.store', 'variant.product.currency', 'variant.attributes.attribute', 'variant.attributes.option', 'variant.images']);
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'variant_id' => $data['variant_id'],
            'qty' => $data['qty'] ?? 1,
        ])->load(['variant.product.store', 'variant.product.currency', 'variant.attributes.attribute', 'variant.attributes.option', 'variant.images']);
    }

    public function updateItem(int $userId, int $itemId, int $qty): ?CartItem
    {
        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart) {
            return null;
        }

        $item = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$item) {
            return null;
        }

        if ($qty <= 0) {
            $item->delete();
            return null;
        }

        $item->update(['qty' => $qty]);
        return $item->fresh(['variant.product.store', 'variant.product.currency', 'variant.attributes.attribute', 'variant.attributes.option', 'variant.images']);
    }

    public function removeItem(int $userId, int $itemId): bool
    {
        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart) {
            return false;
        }

        $item = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$item) {
            return false;
        }

        return $item->delete();
    }

    public function clearCart(int $userId): void
    {
        Cart::where('user_id', $userId)->first()?->items()->delete();
    }

    public function getCartTotal(int $userId): array
    {
        $cart = Cart::with(['items.variant.product.store', 'items.variant.product.currency'])
            ->where('user_id', $userId)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return [
                'subtotal' => 0.0,
                'total_items' => 0,
                'group_by_store' => collect(),
            ];
        }

        $totalItems = $cart->items->sum('qty');
        $subtotal = $cart->items->sum(function ($item) {
            return $item->qty * ($item->variant->price ?? 0);
        });

        $groupByStore = $cart->items->groupBy(function ($item) {
            return $item->variant->product->store_id;
        })->map(function ($items, $storeId) {
            $first = $items->first();
            return [
                'store_id' => $storeId,
                'store_name' => $first->variant->product->store->name,
                'items' => $items,
                'subtotal' => $items->sum(function ($i) {
                    return $i->qty * ($i->variant->price ?? 0);
                }),
            ];
        })->values();

        return [
            'subtotal' => round($subtotal, 2),
            'total_items' => $totalItems,
            'group_by_store' => $groupByStore,
        ];
    }
}
