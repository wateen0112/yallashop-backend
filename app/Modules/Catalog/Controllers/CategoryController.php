<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->getAllWithChildren();

        return ApiResponse::success($categories);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->findWithProducts($id);

        if (! $category) {
            return ApiResponse::error('Category not found', 404);
        }

        return ApiResponse::success($category);
    }

    public function attributes(int $id): JsonResponse
    {
        $attributes = $this->categoryRepository->getAttributes($id);
        return ApiResponse::success($attributes);
    }
}
