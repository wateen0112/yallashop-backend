<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Requests\Api\V1\UpdateSubOrderStatusRequest;
use App\Modules\Vendors\Services\VendorService;
use App\Modules\Vendors\Repositories\StoreRepository;
use App\Support\Enums\ProductStatus;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(
        protected VendorService $vendorService,
        protected StoreRepository $storeRepository
    ) {}

    protected function getStoreId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->relationLoaded('store') && $user->store) {
            return $user->store->id;
        }
        $store = $this->storeRepository->getByOwner($user->id);
        if ($store) {
            return $store->id;
        }

        // Auto-create store for vendor users missing one
        if ($user->role->value === 'vendor') {
            $store = $this->storeRepository->create([
                'owner_user_id' => $user->id,
                'name' => $user->name . "'s Store",
                'city' => 'Unknown',
            ]);
            return $store->id;
        }

        return null;
    }

    public function products(Request $request): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        return ApiResponse::success($this->vendorService->getProducts($storeId));
    }

    public function product(Request $request, int $productId): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        $product = $this->vendorService->getProduct($storeId, $productId);
        if (!$product) return ApiResponse::error('Product not found', 404);
        return ApiResponse::success($product);
    }

    public function createProduct(StoreProductRequest $request): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);

        $data = $request->validated();
        // Vendor-created products always start as pending approval
        $data['status'] = ProductStatus::Pending->value;

        return ApiResponse::success(
            $this->vendorService->createProduct($storeId, $data),
            'Product created',
            201
        );
    }

    public function updateProduct(UpdateProductRequest $request, int $productId): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);

        if ($this->vendorService->productHasActiveOrders($productId)) {
            return ApiResponse::error(
                'Product cannot be updated because it has active orders / لا يمكن تعديل المنتج لوجود طلبات نشطة',
                409
            );
        }

        $product = $this->vendorService->updateProduct($storeId, $productId, $request->validated());
        if (!$product) return ApiResponse::error('Product not found', 404);
        return ApiResponse::success($product, 'Product updated');
    }

    public function deleteProduct(Request $request, int $productId): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        if (!$this->vendorService->deleteProduct($storeId, $productId)) {
            return ApiResponse::error('Product not found', 404);
        }
        return ApiResponse::success(null, 'Product deleted');
    }

    public function orders(Request $request): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        return ApiResponse::success($this->vendorService->getOrders($storeId));
    }

    public function updateOrderStatus(UpdateSubOrderStatusRequest $request, int $subOrderId): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        $order = $this->vendorService->updateOrderStatus($storeId, $subOrderId, $request->validated()['status']);
        if (!$order) return ApiResponse::error('Sub-order not found or invalid status transition', 400);
        return ApiResponse::success($order, 'Order status updated');
    }

    public function analytics(Request $request): JsonResponse
    {
        $storeId = $this->getStoreId($request);
        if (!$storeId) return ApiResponse::error('Vendor store not found', 404);
        return ApiResponse::success($this->vendorService->getAnalytics($storeId));
    }
}
