<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Product;
use App\Support\Enums\ProductStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Product::where('status', ProductStatus::Active->value)
            ->whereHas('store', function ($q) {
                $q->where('status', 'approved');
            })
            ->with(['store', 'currency', 'images']);

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['q']}%")
                  ->orWhere('description', 'like', "%{$filters['q']}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Location-based filtering
        if (!empty($filters['governorate'])) {
            $query->whereHas('store', function ($q) use ($filters) {
                $q->where('governorate', $filters['governorate']);
            });
        }

        if (!empty($filters['city'])) {
            $query->whereHas('store', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }

        if (!empty($filters['area'])) {
            $query->whereHas('store', function ($q) use ($filters) {
                $q->where('area', $filters['area']);
            });
        }

        if (isset($filters['price_min'])) {
            $query->where('base_price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('base_price', '<=', $filters['price_max']);
        }

        $sort = $filters['sort'] ?? 'relevance';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'best_selling':
                $query->orderBy('rating_avg', 'desc');
                break;
            case 'top_rated':
                $query->orderBy('rating_avg', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $filters['per_page'] ?? 12;

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id): ?Product
    {
        return Product::with([
            'store',
            'currency',
            'category.attributes.options',
            'variants.attributes.attribute',
            'variants.attributes.option',
            'variants.images',
            'images',
            'reviews.user',
        ])->find($id);
    }

    public function search(string $query): LengthAwarePaginator
    {
        return Product::where('status', ProductStatus::Active->value)
            ->whereHas('store', function ($q) {
                $q->where('status', 'approved');
            })
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['store', 'currency', 'images'])
            ->orderBy('rating_avg', 'desc')
            ->paginate(12);
    }
}
