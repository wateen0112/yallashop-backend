<?php

namespace App\Modules\Vendors\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Repositories\VendorProductRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorProductController extends Controller
{
    public function __construct(
        protected VendorProductRepository $vendorProductRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $storeId = $request->user()->store?->id;

        if (! $storeId) {
            return ApiResponse::error('Vendor store not found', 404);
        }

        $products = $this->vendorProductRepository->getByStore($storeId);

        return ApiResponse::success($products);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = $request->user()->store?->id;

        if (! $storeId) {
            return ApiResponse::error('Vendor store not found', 404);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|numeric|min:0',
            'currency_id' => 'sometimes|exists:currencies,id',
            'status' => 'sometimes|string',
            'variants' => 'sometimes|array',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'required|integer|min:0',
            'images' => 'sometimes|array',
            'images.*.url' => 'required|string',
            'images.*.sort_order' => 'sometimes|integer',
        ]);

        $validated['store_id'] = $storeId;
        $product = $this->vendorProductRepository->createWithRelations($validated);

        return ApiResponse::success($product, 'Product created', 201);
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $storeId = $request->user()->store?->id;

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'base_price' => 'sometimes|numeric|min:0',
            'currency_id' => 'sometimes|exists:currencies,id',
            'status' => 'sometimes|string',
        ]);

        $product = $this->vendorProductRepository->updateForStore($storeId, $productId, $validated);

        if (! $product) {
            return ApiResponse::error('Product not found', 404);
        }

        return ApiResponse::success($product, 'Product updated');
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $storeId = $request->user()->store?->id;
        $deleted = $this->vendorProductRepository->deleteForStore($storeId, $productId);

        if (! $deleted) {
            return ApiResponse::error('Product not found', 404);
        }

        return ApiResponse::success(null, 'Product deleted');
    }
}
