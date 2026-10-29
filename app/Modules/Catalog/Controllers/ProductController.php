<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productRepository->paginate($request->all());

        return ApiResponse::success($products);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findWithRelations($id);

        if (! $product) {
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
}
