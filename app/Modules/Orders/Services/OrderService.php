<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function listOrders(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->orderRepository->getByUser($userId);
    }

    public function getOrder(int $userId, int $orderId): ?Order
    {
        return $this->orderRepository->findWithSubOrders($userId, $orderId);
    }

    public function cancelOrder(int $userId, int $orderId): bool
    {
        return $this->orderRepository->cancel($userId, $orderId);
    }
}
