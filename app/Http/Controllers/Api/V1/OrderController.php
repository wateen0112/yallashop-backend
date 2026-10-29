<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getByUser($request->user()->id);

        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findWithSubOrders($request->user()->id, $id);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        return ApiResponse::success(new OrderResource($order));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $cancelled = $this->orderRepository->cancel($request->user()->id, $id);

        if (!$cancelled) {
            return ApiResponse::error('Order could not be cancelled', 400);
        }

        return ApiResponse::success(null, 'Order cancelled successfully');
    }
}