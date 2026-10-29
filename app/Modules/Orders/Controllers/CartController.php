<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\CartRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartRepository->getByUserWithItems($request->user()->id);

        return ApiResponse::success($cart);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = $this->cartRepository->addItem($request->user()->id, $validated);

        return ApiResponse::success($item, 'Item added to cart', 201);
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = $this->cartRepository->updateItem($request->user()->id, $itemId, $validated['quantity']);

        if (! $item) {
            return ApiResponse::error('Cart item not found', 404);
        }

        return ApiResponse::success($item, 'Cart item updated');
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $deleted = $this->cartRepository->removeItem($request->user()->id, $itemId);

        if (! $deleted) {
            return ApiResponse::error('Cart item not found', 404);
        }

        return ApiResponse::success(null, 'Item removed from cart');
    }
}
