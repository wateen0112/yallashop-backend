<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\CartItem;
use App\Modules\Orders\Repositories\CartRepository;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    public function getCart(int $userId): array
    {
        $cart = $this->cartRepository->getByUserWithItems($userId);
        if (!$cart) {
            return ['items' => [], 'subtotal' => 0.0, 'total_items' => 0, 'group_by_store' => []];
        }
        $summary = $this->cartRepository->getCartTotal($userId);
        $summary['items'] = $cart->items;
        return $summary;
    }

    public function addItem(int $userId, array $data): CartItem
    {
        $variant = ProductVariant::findOrFail($data['variant_id']);
        $requestedQty = $data['qty'] ?? 1;
        if ($variant->stock_qty < $requestedQty) {
            throw ValidationException::withMessages([
                'qty' => ['Insufficient stock. Available: ' . $variant->stock_qty],
            ]);
        }
        return $this->cartRepository->addItem($userId, $data);
    }

    public function updateItem(int $userId, int $itemId, int $qty): ?CartItem
    {
        $cart = $this->cartRepository->getByUserWithItems($userId);
        $item = $cart?->items->firstWhere('id', $itemId);
        if ($item) {
            $variant = $item->variant;
            if ($variant && $qty > $variant->stock_qty) {
                throw ValidationException::withMessages([
                    'qty' => ['Insufficient stock. Available: ' . $variant->stock_qty],
                ]);
            }
        }
        return $this->cartRepository->updateItem($userId, $itemId, $qty);
    }

    public function removeItem(int $userId, int $itemId): bool
    {
        return $this->cartRepository->removeItem($userId, $itemId);
    }

    public function getCartSummary(int $userId): array
    {
        return $this->cartRepository->getCartTotal($userId);
    }
}
