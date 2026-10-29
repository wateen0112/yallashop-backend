<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreatePayoutRequest;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\StoreVendorRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Requests\Api\V1\UpdateVendorRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Delivery\Repositories\CourierRepository;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Modules\Payments\Repositories\PayoutRepository;
use App\Modules\Users\Models\User;
use App\Modules\Vendors\Repositories\StoreRepository;
use App\Modules\Vendors\Repositories\VendorProductRepository;
use App\Support\Enums\ProductStatus;
use App\Support\Enums\UserRole;
use App\Http\Resources\OrderResource;
use App\Support\Helpers\ApiResponse;
use App\Modules\Vendors\Repositories\VendorOrderRepository;
use App\Http\Requests\Api\V1\UpdateSubOrderStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected StoreRepository $storeRepository,
        protected CourierRepository $courierRepository,
        protected OrderRepository $orderRepository,
        protected PayoutRepository $payoutRepository,
        protected CategoryRepository $categoryRepository,
        protected VendorProductRepository $vendorProductRepository,
        protected ProductRepository $productRepository,
        protected VendorOrderRepository $vendorOrderRepository
    ) {}

    public function vendors(Request $request): JsonResponse
    {
        return ApiResponse::success($this->storeRepository->getAll());
    }

    public function createVendor(StoreVendorRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->storeRepository->create($request->validated()),
            'Vendor created',
            201
        );
    }

    public function updateVendor(UpdateVendorRequest $request, int $id): JsonResponse
    {
        $store = $this->storeRepository->update($id, $request->validated());
        if (!$store) return ApiResponse::error('Store not found', 404);
        return ApiResponse::success($store, 'Vendor updated');
    }

    public function deleteVendor(Request $request, int $id): JsonResponse
    {
        if (!$this->storeRepository->delete($id)) {
            return ApiResponse::error('Store not found', 404);
        }
        return ApiResponse::success(null, 'Vendor deleted');
    }

    public function approveVendor(Request $request, int $id): JsonResponse
    {
        $store = $this->storeRepository->approve($id);
        if (!$store) return ApiResponse::error('Store not found', 404);
        return ApiResponse::success($store, 'Vendor approved');
    }

    public function couriers(Request $request): JsonResponse
    {
        return ApiResponse::success($this->courierRepository->getAll());
    }

    public function approveCourier(Request $request, int $id): JsonResponse
    {
        $courier = $this->courierRepository->findByUser($id);
        if (!$courier) return ApiResponse::error('Courier not found', 404);
        $courier->update(['status' => 'online']);
        return ApiResponse::success($courier, 'Courier approved');
    }

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            if (!$store) {
                return ApiResponse::success(collect()->paginate(20));
            }
            return ApiResponse::success($this->vendorOrderRepository->getByStore($store->id));
        }

        $orders = \App\Modules\Orders\Models\Order::with(['subOrders.items.variant.images', 'subOrders.items.variant.attributes.attribute', 'subOrders.items.variant.attributes.option', 'subOrders.items.variant.product.images', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function updateOrderStatus(UpdateSubOrderStatusRequest $request, int $subOrderId): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            $storeId = $store?->id;
        } else {
            $storeId = null;
        }

        $order = $this->vendorOrderRepository->updateStatus($storeId, $subOrderId, $request->validated()['status']);
        if (!$order) return ApiResponse::error('Sub-order not found or invalid status transition', 400);
        return ApiResponse::success($order, 'Order status updated');
    }

    public function disputes(Request $request): JsonResponse
    {
        return ApiResponse::success(['disputes' => []]);
    }

    public function payouts(Request $request): JsonResponse
    {
        return ApiResponse::success($this->payoutRepository->getAll($request->all()));
    }

    public function createPayout(CreatePayoutRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->payoutRepository->create($request->validated()),
            'Payout created',
            201
        );
    }

    public function overview(Request $request): JsonResponse
    {
        $totalVendors = \App\Modules\Vendors\Models\Store::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalCouriers = \App\Modules\Delivery\Models\Courier::count();
        $totalOrders = \App\Modules\Orders\Models\Order::count();
        $totalRevenue = \App\Modules\Orders\Models\Order::where('payment_status', 'paid')->sum('grand_total');

        return ApiResponse::success([
            'total_vendors' => $totalVendors,
            'total_customers' => $totalCustomers,
            'total_couriers' => $totalCouriers,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ]);
    }

    public function listCategories(Request $request): JsonResponse
    {
        return ApiResponse::success($this->categoryRepository->getAll());
    }

    public function createCategory(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->all();
        $user = $request->user();

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $data['image'] = '/storage/' . $path;
            unset($data['image_file']);
        } else {
            unset($data['image_file']);
        }

        if (isset($data['attributes']) && is_string($data['attributes'])) {
            $data['attributes'] = json_decode($data['attributes'], true);
        }

        // Vendor-created categories get "pending" status
        if ($user->role === UserRole::Vendor) {
            $data['status'] = 'pending';
            $data['created_by'] = $user->id;
        } else {
            $data['status'] = 'approved';
        }

        return ApiResponse::success(
            $this->categoryRepository->create($data),
            'Category created',
            201
        );
    }

    public function updateCategory(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $data = $request->all();
        $user = $request->user();

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $data['image'] = '/storage/' . $path;
            unset($data['image_file']);
        } else {
            unset($data['image_file']);
        }

        if (isset($data['attributes']) && is_string($data['attributes'])) {
            $data['attributes'] = json_decode($data['attributes'], true);
        }

        // Vendors can only update their own categories
        if ($user->role === UserRole::Vendor) {
            $category = Category::find($id);
            if (!$category || $category->created_by !== $user->id) {
                return ApiResponse::error('Category not found', 404);
            }
        }

        $category = $this->categoryRepository->update($id, $data);
        if (!$category) return ApiResponse::error('Category not found', 404);
        return ApiResponse::success($category, 'Category updated');
    }

    public function deleteCategory(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Vendors can only delete their own categories
        if ($user->role === UserRole::Vendor) {
            $category = Category::find($id);
            if (!$category || $category->created_by !== $user->id) {
                return ApiResponse::error('Category not found', 404);
            }
        }

        if (!$this->categoryRepository->delete($id)) {
            return ApiResponse::error('Category not found', 404);
        }
        return ApiResponse::success(null, 'Category deleted');
    }

    public function listProducts(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Product::with(['store', 'category', 'currency', 'variants', 'images']);

        if ($user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            if ($store) {
                $query->where('store_id', $store->id);
            } else {
                return ApiResponse::success(collect()->paginate(20));
            }
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);
        return ApiResponse::success($products);
    }

    public function showProduct(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $storeId = null;
        if ($user && $user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            $storeId = $store?->id;
        }
        $product = $this->vendorProductRepository->findForStore($storeId, $id);

        if (!$product) {
            return ApiResponse::error('Product not found', 404);
        }

        return ApiResponse::success($product);
    }

    public function createProduct(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Vendor-created products get "pending" status
        if ($user->role === UserRole::Vendor) {
            $data['status'] = ProductStatus::Pending->value;
        }

        return ApiResponse::success(
            $this->vendorProductRepository->createWithRelations($data),
            'Product created',
            201
        );
    }

    public function updateProduct(UpdateProductRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $storeId = null;
        if ($user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            $storeId = $store?->id;
        }
        $product = $this->vendorProductRepository->updateForStore($storeId, $id, $request->validated());
        if (!$product) return ApiResponse::error('Product not found', 404);
        return ApiResponse::success($product, 'Product updated');
    }

    public function deleteProduct(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $storeId = null;
        if ($user->role === UserRole::Vendor) {
            $store = $this->storeRepository->getByOwner($user->id);
            $storeId = $store?->id;
        }
        if (!$this->vendorProductRepository->deleteForStore($storeId, $id)) {
            return ApiResponse::error('Product not found', 404);
        }
        return ApiResponse::success(null, 'Product deleted');
    }

    public function listCategoryAttributes(int $categoryId): JsonResponse
    {
        $attributes = \App\Modules\Catalog\Models\CategoryAttribute::with('options')
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->get();
        return ApiResponse::success($attributes);
    }

    public function createCategoryAttribute(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string|in:select,text',
            'is_required' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'options' => 'sometimes|array',
            'options.*.value' => 'required|string|max:255',
            'options.*.sort_order' => 'sometimes|integer|min:0',
        ]);

        $attribute = \App\Modules\Catalog\Models\CategoryAttribute::create([
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? 'select',
            'is_required' => $validated['is_required'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $opt) {
                $attribute->options()->create([
                    'value' => $opt['value'],
                    'sort_order' => $opt['sort_order'] ?? 0,
                ]);
            }
        }

        return ApiResponse::success($attribute->load('options'), 'Attribute created', 201);
    }

    public function updateCategoryAttribute(Request $request, int $id): JsonResponse
    {
        $attribute = \App\Modules\Catalog\Models\CategoryAttribute::find($id);
        if (!$attribute) return ApiResponse::error('Attribute not found', 404);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:select,text',
            'is_required' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $attribute->update($validated);
        return ApiResponse::success($attribute->load('options'), 'Attribute updated');
    }

    public function deleteCategoryAttribute(int $id): JsonResponse
    {
        $attribute = \App\Modules\Catalog\Models\CategoryAttribute::find($id);
        if (!$attribute) return ApiResponse::error('Attribute not found', 404);
        $attribute->delete();
        return ApiResponse::success(null, 'Attribute deleted');
    }

    public function addAttributeOption(Request $request, int $attributeId): JsonResponse
    {
        $attribute = \App\Modules\Catalog\Models\CategoryAttribute::find($attributeId);
        if (!$attribute) return ApiResponse::error('Attribute not found', 404);

        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $option = $attribute->options()->create([
            'value' => $validated['value'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return ApiResponse::success($option, 'Option created', 201);
    }

    public function deleteAttributeOption(int $id): JsonResponse
    {
        $option = \App\Modules\Catalog\Models\CategoryAttributeOption::find($id);
        if (!$option) return ApiResponse::error('Option not found', 404);
        $option->delete();
        return ApiResponse::success(null, 'Option deleted');
    }

    // --- Product Approval ---

    public function approveProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) return ApiResponse::error('Product not found', 404);
        $product->update(['status' => ProductStatus::Active->value]);
        return ApiResponse::success($product->fresh(), 'Product approved');
    }

    public function rejectProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) return ApiResponse::error('Product not found', 404);
        $product->update(['status' => ProductStatus::Rejected->value]);
        return ApiResponse::success($product->fresh(), 'Product rejected');
    }

    public function updateProductStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', ProductStatus::values()),
        ]);

        $product = Product::find($id);
        if (!$product) return ApiResponse::error('Product not found', 404);

        $product->update(['status' => $validated['status']]);
        return ApiResponse::success($product->fresh(), 'Product status updated');
    }

    // --- Category Approval ---

    public function approveCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) return ApiResponse::error('Category not found', 404);
        $category->update(['status' => 'approved']);
        return ApiResponse::success($category->fresh(), 'Category approved');
    }

    public function rejectCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) return ApiResponse::error('Category not found', 404);
        $category->update(['status' => 'rejected']);
        return ApiResponse::success($category->fresh(), 'Category rejected');
    }

    public function updateCategoryStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected,disabled',
        ]);

        $category = Category::find($id);
        if (!$category) return ApiResponse::error('Category not found', 404);

        $category->update(['status' => $validated['status']]);
        return ApiResponse::success($category->fresh(), 'Category status updated');
    }
}
