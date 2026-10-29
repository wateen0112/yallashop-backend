<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateLocationRequest;
use App\Modules\Delivery\Services\DeliveryService;
use App\Modules\Delivery\Repositories\CourierRepository;
use App\Modules\Delivery\Repositories\CourierTaskRepository;
use App\Http\Resources\SubOrderResource;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected CourierTaskRepository $courierTaskRepository,
        protected CourierRepository $courierRepository
    ) {}

    public function tasks(Request $request): JsonResponse
    {
        return ApiResponse::success(SubOrderResource::collection($this->deliveryService->getAvailableTasks($request->user()->id)));
    }

    public function activeTasks(Request $request): JsonResponse
    {
        return ApiResponse::success(SubOrderResource::collection($this->courierTaskRepository->getActiveTasks($request->user()->id)));
    }

    public function acceptTask(Request $request, int $subOrderId): JsonResponse
    {
        if (!$this->deliveryService->acceptTask($request->user()->id, $subOrderId)) {
            return ApiResponse::error('Could not accept task', 400);
        }
        return ApiResponse::success(null, 'Task accepted');
    }

    public function rejectTask(Request $request, int $subOrderId): JsonResponse
    {
        if (!$this->deliveryService->rejectTask($request->user()->id, $subOrderId)) {
            return ApiResponse::error('Could not reject task', 400);
        }
        return ApiResponse::success(null, 'Task rejected');
    }

    public function verifyPickupCode(Request $request, int $subOrderId): JsonResponse
    {
        $validated = $request->validate([
            'pickup_code' => 'required|string|size:6',
        ]);

        if (!$this->deliveryService->verifyPickupCode(
            $request->user()->id,
            $subOrderId,
            $validated['pickup_code']
        )) {
            return ApiResponse::error('Invalid pickup code or task not found', 400);
        }
        return ApiResponse::success(null, 'Pickup verified');
    }

    public function verifyDeliveryCode(Request $request, int $subOrderId): JsonResponse
    {
        $validated = $request->validate([
            'delivery_code' => 'required|string|size:6',
        ]);

        if (!$this->deliveryService->verifyDeliveryCode(
            $request->user()->id,
            $subOrderId,
            $validated['delivery_code']
        )) {
            return ApiResponse::error('Invalid delivery code or task not found', 400);
        }
        return ApiResponse::success(null, 'Delivery confirmed');
    }

    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $this->deliveryService->updateLocation($request->user()->id, $request->validated());
        return ApiResponse::success(null, 'Location updated');
    }

    public function status(Request $request): JsonResponse
    {
        $courier = $this->courierRepository->findByUser($request->user()->id);
        if (!$courier) {
            return ApiResponse::error('Courier profile not found', 404);
        }
        return ApiResponse::success(['status' => $courier->status]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:online,offline',
        ]);

        $courier = $this->courierRepository->findByUser($request->user()->id);
        if (!$courier) {
            return ApiResponse::error('Courier profile not found', 404);
        }

        $this->courierRepository->updateStatus($request->user()->id, $validated['status']);

        return ApiResponse::success(['status' => $validated['status']], 'Status updated');
    }

    public function earnings(Request $request): JsonResponse
    {
        return ApiResponse::success($this->deliveryService->getEarnings($request->user()->id));
    }
}