<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ]);

        $order = $this->orderRepository->placeOrder(
            $request->user()->id,
            $validated
        );

        return ApiResponse::success($order, 'Order placed successfully', 201);
    }
}
