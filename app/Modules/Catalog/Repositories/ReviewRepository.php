<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Review;
use App\Modules\Vendors\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewRepository
{
    public function getByProduct(int $productId): LengthAwarePaginator
    {
        return Review::where('reviewable_id', $productId)
            ->where('reviewable_type', 'product')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getByReviewable(string $type, int $id): LengthAwarePaginator
    {
        return Review::where('reviewable_id', $id)
            ->where('reviewable_type', $type)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function create(int $userId, array $data): Review
    {
        $review = Review::create([
            'reviewable_type' => $data['reviewable_type'],
            'reviewable_id' => $data['reviewable_id'],
            'user_id' => $userId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'order_id' => $data['order_id'] ?? null,
        ]);

        // Update rating_avg on the reviewable model
        $this->updateRatingAvg($data['reviewable_type'], $data['reviewable_id']);

        return $review;
    }

    public function getRatingStats(int $productId): array
    {
        $ratings = Review::where('reviewable_id', $productId)
            ->where('reviewable_type', 'product')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $avg = Review::where('reviewable_id', $productId)
            ->where('reviewable_type', 'product')
            ->avg('rating');

        $countPerStar = [];
        for ($i = 1; $i <= 5; $i++) {
            $countPerStar[$i] = $ratings[$i] ?? 0;
        }

        return [
            'average' => round((float) $avg, 1),
            'total' => array_sum($countPerStar),
            'count_per_star' => $countPerStar,
        ];
    }

    private function updateRatingAvg(string $type, int $id): void
    {
        $avg = Review::where('reviewable_type', $type)
            ->where('reviewable_id', $id)
            ->avg('rating');

        $roundedAvg = round((float) $avg, 1);

        if ($type === 'product') {
            Product::where('id', $id)->update(['rating_avg' => $roundedAvg]);
        } elseif ($type === 'store') {
            Store::where('id', $id)->update(['rating_avg' => $roundedAvg]);
        }
    }
}