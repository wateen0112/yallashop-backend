<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\CourierController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Modules\Marketing\Controllers\CarouselAdController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Public auth
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('otp/request', [AuthController::class, 'requestOtp']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Public catalog
    Route::prefix('catalog')->group(function () {
        Route::get('categories', [CatalogController::class, 'categories']);
        Route::get('categories/{id}', [CatalogController::class, 'category']);
        Route::get('categories/{id}/products', [CatalogController::class, 'categoryProducts']);
        Route::get('products', [CatalogController::class, 'products']);
        Route::get('products/{id}', [CatalogController::class, 'product']);
        Route::get('search', [CatalogController::class, 'search']);
        Route::get('stores/{id}', [CatalogController::class, 'store']);
    });

    // Public carousel ads
    Route::get('carousel-ads/active', [CarouselAdController::class, 'getActive']);

    // Public currencies
    Route::get('currencies', [CurrencyController::class, 'index']);
    Route::get('currencies/default', [CurrencyController::class, 'default']);

    // Public locations
    Route::get('locations/governorates', [LocationController::class, 'governorates']);
    Route::get('locations/cities', [LocationController::class, 'cities']);
    Route::get('locations/areas', [LocationController::class, 'areas']);

    // Cart (authenticated)
    Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::patch('items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('items/{itemId}', [CartController::class, 'removeItem']);
    });

    // Profile (authenticated)
    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::patch('/', [ProfileController::class, 'update']);
        Route::post('upload', [FileController::class, 'upload']);
    });

    // Addresses (authenticated)
    Route::middleware('auth:sanctum')->prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::patch('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::patch('/{id}/default', [AddressController::class, 'setDefault']);
    });

    // Checkout
    Route::middleware('auth:sanctum')->prefix('checkout')->group(function () {
        Route::post('/', [CheckoutController::class, 'checkout']);
    });

    // Orders
    Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('{id}', [OrderController::class, 'show']);
        Route::post('{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Reviews
    Route::middleware('auth:sanctum')->prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('{type}/{id}', [ReviewController::class, 'index']);
        Route::get('eligibility/{subOrderId}', [ReviewController::class, 'checkEligibility']);
    });

    // FCM Token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('fcm-token', [FcmTokenController::class, 'store']);
        Route::delete('fcm-token', [FcmTokenController::class, 'destroy']);
    });

    // Notifications
    Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('read-all', [NotificationController::class, 'markAllRead']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
    });

    // Vendor (authenticated)
    Route::middleware(['auth:sanctum'])->prefix('vendor')->group(function () {
        Route::get('products', [VendorController::class, 'products']);
        Route::get('products/{productId}', [VendorController::class, 'product']);
        Route::post('products', [VendorController::class, 'createProduct']);
        Route::put('products/{productId}', [VendorController::class, 'updateProduct']);
        Route::delete('products/{productId}', [VendorController::class, 'deleteProduct']);
        Route::get('orders', [VendorController::class, 'orders']);
        Route::put('orders/{subOrderId}/status', [VendorController::class, 'updateOrderStatus']);
        Route::get('analytics/overview', [VendorController::class, 'analytics']);

        // File Upload
        Route::post('upload', [FileController::class, 'upload']);

        // Category Attributes (read-only for product creation)
        Route::get('categories/{categoryId}/attributes', [AdminController::class, 'listCategoryAttributes']);
        Route::post('attributes/{attributeId}/options', [AdminController::class, 'addAttributeOption']);
    });

    // Courier
    Route::middleware(['auth:sanctum'])->prefix('courier')->group(function () {
        Route::get('tasks', [CourierController::class, 'tasks']);
        Route::get('tasks/active', [CourierController::class, 'activeTasks']);
        Route::post('tasks/{subOrderId}/accept', [CourierController::class, 'acceptTask']);
        Route::post('tasks/{subOrderId}/reject', [CourierController::class, 'rejectTask']);
        Route::post('tasks/{subOrderId}/verify-pickup', [CourierController::class, 'verifyPickupCode']);
        Route::post('tasks/{subOrderId}/verify-delivery', [CourierController::class, 'verifyDeliveryCode']);
        Route::post('location', [CourierController::class, 'updateLocation']);
        Route::get('status', [CourierController::class, 'status']);
        Route::post('status', [CourierController::class, 'updateStatus']);
        Route::get('earnings', [CourierController::class, 'earnings']);
    });

    // Admin
    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        // Vendors
        Route::get('vendors', [AdminController::class, 'vendors']);
        Route::post('vendors', [AdminController::class, 'createVendor']);
        Route::put('vendors/{id}', [AdminController::class, 'updateVendor']);
        Route::delete('vendors/{id}', [AdminController::class, 'deleteVendor']);
        Route::post('vendors/{id}/approve', [AdminController::class, 'approveVendor']);

        // Categories
        Route::get('categories', [AdminController::class, 'listCategories']);
        Route::post('categories', [AdminController::class, 'createCategory']);
        Route::put('categories/{id}', [AdminController::class, 'updateCategory']);
        Route::delete('categories/{id}', [AdminController::class, 'deleteCategory']);
        Route::post('categories/{id}/approve', [AdminController::class, 'approveCategory']);
        Route::post('categories/{id}/reject', [AdminController::class, 'rejectCategory']);
        Route::post('categories/{id}/status', [AdminController::class, 'updateCategoryStatus']);

        // Products
        Route::get('products', [AdminController::class, 'listProducts']);
        Route::get('products/{id}', [AdminController::class, 'showProduct']);
        Route::post('products', [AdminController::class, 'createProduct']);
        Route::put('products/{id}', [AdminController::class, 'updateProduct']);
        Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);
        Route::post('products/{id}/approve', [AdminController::class, 'approveProduct']);
        Route::post('products/{id}/reject', [AdminController::class, 'rejectProduct']);
        Route::post('products/{id}/status', [AdminController::class, 'updateProductStatus']);

        // Category Attributes
        Route::get('categories/{categoryId}/attributes', [AdminController::class, 'listCategoryAttributes']);
        Route::post('categories/{categoryId}/attributes', [AdminController::class, 'createCategoryAttribute']);
        Route::put('attributes/{id}', [AdminController::class, 'updateCategoryAttribute']);
        Route::delete('attributes/{id}', [AdminController::class, 'deleteCategoryAttribute']);
        Route::post('attributes/{attributeId}/options', [AdminController::class, 'addAttributeOption']);
        Route::delete('options/{id}', [AdminController::class, 'deleteAttributeOption']);

        // Couriers
        Route::get('couriers', [AdminController::class, 'couriers']);
        Route::post('couriers/{id}/approve', [AdminController::class, 'approveCourier']);

        // Orders
        Route::get('orders', [AdminController::class, 'orders']);
        Route::put('orders/{subOrderId}/status', [AdminController::class, 'updateOrderStatus']);

        // Disputes
        Route::get('disputes', [AdminController::class, 'disputes']);

        // Payouts
        Route::get('payouts', [AdminController::class, 'payouts']);
        Route::post('payouts', [AdminController::class, 'createPayout']);

        // File Upload
        Route::post('upload', [FileController::class, 'upload']);

        // Carousel Ads
        Route::get('carousel-ads', [CarouselAdController::class, 'index']);
        Route::post('carousel-ads', [CarouselAdController::class, 'store']);
        Route::put('carousel-ads/{id}', [CarouselAdController::class, 'update']);
        Route::delete('carousel-ads/{id}', [CarouselAdController::class, 'destroy']);

        // Analytics
        Route::get('analytics/overview', [AdminController::class, 'overview']);

        // Currencies
        Route::get('currencies', [CurrencyController::class, 'adminIndex']);
        Route::post('currencies', [CurrencyController::class, 'store']);
        Route::put('currencies/{id}', [CurrencyController::class, 'update']);
        Route::delete('currencies/{id}', [CurrencyController::class, 'destroy']);
        Route::post('currencies/{id}/default', [CurrencyController::class, 'setDefault']);
    });
});
