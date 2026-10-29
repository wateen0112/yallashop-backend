<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->getAllWithChildren();
    }

    public function getCategoryTree(): Collection
    {
        return $this->categoryRepository->getTree();
    }

    public function getCategoryWithProducts(int $id): ?Category
    {
        return $this->categoryRepository->findWithProducts($id);
    }
}
