<?php

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Repositories\CourierTaskRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierTaskController extends Controller
{
    public function __construct(
        protected CourierTaskRepository $courierTaskRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->courierTaskRepository->getAvailableTasks($request->user()->id);

        return ApiResponse::success($tasks);
    }

    public function accept(Request $request, int $subOrderId): JsonResponse
    {
        $accepted = $this->courierTaskRepository->acceptTask($request->user()->id, $subOrderId);

        if (! $accepted) {
            return ApiResponse::error('Could not accept task', 400);
        }

        return ApiResponse::success(null, 'Task accepted');
    }

    public function reject(Request $request, int $subOrderId): JsonResponse
    {
        $rejected = $this->courierTaskRepository->rejectTask($request->user()->id, $subOrderId);

        if (! $rejected) {
            return ApiResponse::error('Could not reject task', 400);
        }

        return ApiResponse::success(null, 'Task rejected');
    }

    public function pickupConfirm(Request $request, int $subOrderId): JsonResponse
    {
        $confirmed = $this->courierTaskRepository->confirmPickup($request->user()->id, $subOrderId);

        if (! $confirmed) {
            return ApiResponse::error('Could not confirm pickup', 400);
        }

        return ApiResponse::success(null, 'Pickup confirmed');
    }

    public function deliverConfirm(Request $request, int $subOrderId): JsonResponse
    {
        $confirmed = $this->courierTaskRepository->confirmDelivery($request->user()->id, $subOrderId);

        if (! $confirmed) {
            return ApiResponse::error('Could not confirm delivery', 400);
        }

        return ApiResponse::success(null, 'Delivery confirmed');
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $this->courierTaskRepository->updateLocation($request->user()->id, $validated);

        return ApiResponse::success(null, 'Location updated');
    }
}
