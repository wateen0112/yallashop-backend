<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Vendors\Models\Store;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    public function categories(): JsonResponse
    {
        $categories = $this->categoryRepository->getTree();

        return ApiResponse::success($categories);
    }

    public function category(int $id): JsonResponse
    {
        $category = $this->categoryRepository->findWithProducts($id);

        if (!$category) {
            return ApiResponse::error('Category not found', 404);
        }

        return ApiResponse::success($category);
    }

    public function categoryProducts(int $id): JsonResponse
    {
        $category = $this->categoryRepository->findWithProducts($id);

        if (!$category) {
            return ApiResponse::error('Category not found', 404);
        }

        $products = $this->productRepository->paginate(['category_id' => $id]);

        return ApiResponse::success([
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $products = $this->productRepository->paginate($request->all());

        return ApiResponse::success($products);
    }

    public function product(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->findWithRelations($id);

        if (!$product) {
            return ApiResponse::error('Product not found', 404);
        }

        return ApiResponse::success($product);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => 'required|string|min:2']);

        $products = $this->productRepository->search($validated['q']);

        return ApiResponse::success($products);
    }

    public function store(int $id): JsonResponse
    {
        $store = Store::with([
            'owner',
            'workingHours',
            'products.variants',
            'products.images',
        ])->find($id);

        if (!$store) {
            return ApiResponse::error('Store not found', 404);
        }

        return ApiResponse::success($store);
    }
}
