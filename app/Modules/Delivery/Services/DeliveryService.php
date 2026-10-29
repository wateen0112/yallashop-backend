<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Repositories\CourierTaskRepository;
use App\Modules\Delivery\Repositories\EarningsRepository;
use App\Modules\Delivery\Repositories\CourierRepository;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Delivery\Models\Courier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        protected CourierTaskRepository $taskRepository,
        protected EarningsRepository $earningsRepository,
        protected CourierRepository $courierRepository
    ) {}

    public function getAvailableTasks(int $userId): Collection
    {
        return $this->taskRepository->getAvailableTasks($userId);
    }

    public function acceptTask(int $userId, int $subOrderId): bool
    {
        return $this->taskRepository->acceptTask($userId, $subOrderId);
    }

    public function rejectTask(int $userId, int $subOrderId): bool
    {
        return $this->taskRepository->rejectTask($userId, $subOrderId);
    }

    public function verifyPickupCode(int $userId, int $subOrderId, string $pickupCode): bool
    {
        return $this->taskRepository->verifyPickupCode($userId, $subOrderId, $pickupCode);
    }

    public function verifyDeliveryCode(int $userId, int $subOrderId, string $deliveryCode): bool
    {
        return $this->taskRepository->verifyDeliveryCode($userId, $subOrderId, $deliveryCode);
    }

    public function updateLocation(int $userId, array $location): void
    {
        $this->taskRepository->updateLocation($userId, $location);
    }

    public function getEarnings(int $userId): array
    {
        $courier = $this->courierRepository->findByUser($userId);
        if (!$courier) {
            return [
                'total_earnings' => 0,
                'wallet_balance' => 0,
                'completed_deliveries' => 0,
            ];
        }

        return [
            'total_earnings' => (float) $this->earningsRepository->getTotalEarnings($courier->id),
            'wallet_balance' => (float) $courier->wallet_balance,
            'completed_deliveries' => $this->earningsRepository->getCompletedDeliveries($courier->id),
        ];
    }
}
