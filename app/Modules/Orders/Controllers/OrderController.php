<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getByUser($request->user()->id);

        return ApiResponse::success($orders);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findWithSubOrders($request->user()->id, $id);

        if (! $order) {
            return ApiResponse::error('Order not found', 404);
        }

        return ApiResponse::success($order);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ]);

        $order = $this->orderRepository->placeOrder($request->user()->id, $validated);

        return ApiResponse::success($order, 'Order placed successfully', 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $cancelled = $this->orderRepository->cancel($request->user()->id, $id);

        if (! $cancelled) {
            return ApiResponse::error('Order could not be cancelled', 400);
        }

        return ApiResponse::success(null, 'Order cancelled successfully');
    }
}
