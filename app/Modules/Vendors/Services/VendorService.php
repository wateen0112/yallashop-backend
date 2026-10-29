<?php

namespace App\Modules\Vendors\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\SubOrder;
use App\Modules\Vendors\Repositories\VendorProductRepository;
use App\Modules\Vendors\Repositories\VendorOrderRepository;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\ProductStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class VendorService
{
    public function __construct(
        protected VendorProductRepository $productRepository,
        protected VendorOrderRepository $orderRepository
    ) {}

    public function getProducts(int $storeId): LengthAwarePaginator
    {
        return $this->productRepository->getByStore($storeId);
    }

    public function getProduct(int $storeId, int $productId): ?Product
    {
        return $this->productRepository->findForStore($storeId, $productId);
    }

    public function productHasActiveOrders(int $productId): bool
    {
        return $this->productRepository->hasActiveOrders($productId);
    }

    public function createProduct(int $storeId, array $data): Product
    {
        $data['store_id'] = $storeId;
        return $this->productRepository->createWithRelations($data);
    }

    public function updateProduct(int $storeId, int $productId, array $data): ?Product
    {
        $product = $this->productRepository->findForStore($storeId, $productId);
        if (!$product) {
            return null;
        }

        // Any change to a live product requires re-approval, unless archiving it
        if ($product->status === ProductStatus::Active->value
            && ($data['status'] ?? null) !== ProductStatus::Archived->value) {
            $data['status'] = ProductStatus::Pending->value;
        }

        return $this->productRepository->updateForStore($storeId, $productId, $data);
    }

    public function deleteProduct(int $storeId, int $productId): bool
    {
        return $this->productRepository->deleteForStore($storeId, $productId);
    }

    public function getOrders(int $storeId): LengthAwarePaginator
    {
        return $this->orderRepository->getByStore($storeId);
    }

    public function updateOrderStatus(int $storeId, int $subOrderId, string $status): ?SubOrder
    {
        return $this->orderRepository->updateStatus($storeId, $subOrderId, $status);
    }

    public function getAnalytics(int $storeId): array
    {
        $totalProducts = Product::where('store_id', $storeId)->count();
        $totalOrders = SubOrder::where('store_id', $storeId)->count();
        $pendingOrders = $this->orderRepository->getPendingCount($storeId);
        $totalRevenue = SubOrder::where('store_id', $storeId)
            ->where('status', OrderStatus::Delivered)
            ->sum('delivery_fee');

        return [
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_revenue' => $totalRevenue,
        ];
    }
}
