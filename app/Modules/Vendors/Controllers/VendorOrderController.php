<?php

namespace App\Modules\Vendors\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Repositories\VendorOrderRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    public function __construct(
        protected VendorOrderRepository $vendorOrderRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $storeId = $request->user()->store?->id;

        if (! $storeId) {
            return ApiResponse::error('Vendor store not found', 404);
        }

        $orders = $this->vendorOrderRepository->getByStore($storeId);

        return ApiResponse::success($orders);
    }

    public function updateStatus(Request $request, int $subOrderId): JsonResponse
    {
        $storeId = $request->user()->store?->id;

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $order = $this->vendorOrderRepository->updateStatus($storeId, $subOrderId, $validated['status']);

        if (! $order) {
            return ApiResponse::error('Sub-order not found', 404);
        }

        return ApiResponse::success($order, 'Order status updated');
    }
}
