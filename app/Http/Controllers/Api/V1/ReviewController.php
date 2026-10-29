<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Modules\Catalog\Models\Review;
use App\Modules\Catalog\Repositories\ReviewRepository;
use App\Modules\Orders\Models\SubOrder;
use App\Support\Enums\OrderStatus;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewRepository $reviewRepository
    ) {}

    public function store(ReviewRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Verify the order is delivered and belongs to the customer
        $subOrder = SubOrder::where('id', $validated['order_id'])
            ->where('status', OrderStatus::Delivered)
            ->first();

        if (!$subOrder) {
            return ApiResponse::error('Order not found or not delivered', 404);
        }

        // Check if user already reviewed this item for this order
        $exists = Review::where('user_id', $user->id)
            ->where('reviewable_type', $validated['reviewable_type'])
            ->where('reviewable_id', $validated['reviewable_id'])
            ->where('order_id', $validated['order_id'])
            ->exists();

        if ($exists) {
            return ApiResponse::error('You have already reviewed this item', 400);
        }

        $review = $this->reviewRepository->create($user->id, [
            'reviewable_type' => $validated['reviewable_type'],
            'reviewable_id' => $validated['reviewable_id'],
            'order_id' => $validated['order_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return ApiResponse::success(new ReviewResource($review), 'Review submitted');
    }

    public function index(Request $request, string $type, int $id): JsonResponse
    {
        $reviews = $this->reviewRepository->getByReviewable($type, $id);
        return ApiResponse::success(ReviewResource::collection($reviews));
    }

    public function checkEligibility(Request $request, int $subOrderId): JsonResponse
    {
        $user = $request->user();

        $subOrder = SubOrder::with(['items.variant.product'])
            ->where('id', $subOrderId)
            ->where('status', OrderStatus::Delivered)
            ->first();

        if (!$subOrder) {
            return ApiResponse::error('Order not found or not delivered', 404);
        }

        // Check which items have already been reviewed
        $reviewedProducts = Review::where('user_id', $user->id)
            ->where('order_id', $subOrderId)
            ->where('reviewable_type', 'product')
            ->pluck('reviewable_id')
            ->toArray();

        $reviewedStore = Review::where('user_id', $user->id)
            ->where('order_id', $subOrderId)
            ->where('reviewable_type', 'store')
            ->where('reviewable_id', $subOrder->store_id)
            ->exists();

        // Build list of items to review
        $itemsToReview = [];
        $reviewedProductIds = [];

        foreach ($subOrder->items as $item) {
            $product = $item->variant->product ?? null;
            if ($product && !in_array($product->id, $reviewedProducts)) {
                $itemsToReview[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->title,
                    'image' => $product->images->first()?->url ?? null,
                ];
            }
            if ($product) {
                $reviewedProductIds[] = $product->id;
            }
        }

        // Add store if not reviewed
        if (!$reviewedStore) {
            $store = $subOrder->store;
            if ($store) {
                $itemsToReview[] = [
                    'type' => 'store',
                    'id' => $store->id,
                    'name' => $store->name,
                    'image' => $store->logo,
                ];
            }
        }

        return ApiResponse::success([
            'can_review' => count($itemsToReview) > 0,
            'items' => $itemsToReview,
            'sub_order_id' => $subOrder->id,
        ]);
    }
}