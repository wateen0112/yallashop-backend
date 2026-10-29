<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function listProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters);
    }

    public function getProduct(int $id): ?Product
    {
        return $this->productRepository->findWithRelations($id);
    }

    public function searchProducts(string $query): LengthAwarePaginator
    {
        return $this->productRepository->search($query);
    }
}
