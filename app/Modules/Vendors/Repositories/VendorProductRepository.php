<?php

namespace App\Modules\Vendors\Repositories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\ProductVariantAttribute;
use App\Modules\Catalog\Models\ProductVariantImage;
use App\Modules\Orders\Models\SubOrder;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\ProductStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorProductRepository
{
    public function getByStore(int $storeId): LengthAwarePaginator
    {
        return Product::with(['variants.attributes', 'variants.images', 'images', 'currency'])
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function createWithRelations(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'store_id' => $data['store_id'],
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'currency_id' => $data['currency_id'] ?? null,
                'status' => $data['status'] ?? ProductStatus::Pending->value,
            ]);

            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $sku = $variantData['sku'] ?? $this->generateSku($product->id);
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'price' => $variantData['price'] ?? $data['base_price'],
                        'stock_qty' => $variantData['stock_qty'] ?? 0,
                    ]);

                    if (!empty($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attr) {
                            ProductVariantAttribute::create([
                                'product_variant_id' => $variant->id,
                                'attribute_id' => $attr['attribute_id'],
                                'option_id' => $attr['option_id'] ?? null,
                                'value' => $attr['value'] ?? null,
                            ]);
                        }
                    }

                    // Handle variant images
                    if (!empty($variantData['images'])) {
                        foreach ($variantData['images'] as $index => $item) {
                            $url = is_array($item) ? ($item['url'] ?? '') : $item;

                            // Skip blob URLs
                            if (str_starts_with($url, 'blob:')) continue;

                            ProductVariantImage::create([
                                'product_variant_id' => $variant->id,
                                'url' => $url,
                                'type' => is_array($item) ? ($item['type'] ?? 'image') : 'image',
                                'sort_order' => is_array($item) ? ($item['sort_order'] ?? $index) : $index,
                            ]);
                        }
                    }
                }
            }

            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $item) {
                    $url = is_array($item) ? ($item['url'] ?? '') : $item;
                    
                    // Skip blob URLs
                    if (str_starts_with($url, 'blob:')) continue;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $url,
                        'type' => is_array($item) ? ($item['type'] ?? 'image') : 'image',
                        'sort_order' => is_array($item) ? ($item['sort_order'] ?? $index) : $index,
                    ]);
                }
            }

            return $product->load(['variants.attributes', 'variants.images', 'images', 'currency']);
        });
    }

    protected function generateSku(int $productId): string
    {
        return 'PROD-' . $productId . '-' . strtoupper(str()->random(5));
    }

    public function updateForStore(?int $storeId, int $productId, array $data): ?Product
    {
        $product = Product::where('id', $productId);
        if ($storeId !== null) {
            $product->where('store_id', $storeId);
        }

        $product = $product->first();
        if (!$product) {
            return null;
        }

        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'title' => $data['title'] ?? $product->title,
                'description' => $data['description'] ?? $product->description,
                'base_price' => $data['base_price'] ?? $product->base_price,
                'currency_id' => $data['currency_id'] ?? $product->currency_id,
                'status' => $data['status'] ?? $product->status,
            ]);

            if (!empty($data['variants'])) {
                $product->variants()->delete();
                foreach ($data['variants'] as $variantData) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'] ?? null,
                        'price' => $variantData['price'] ?? $product->base_price,
                        'stock_qty' => $variantData['stock_qty'] ?? 0,
                    ]);

                    if (!empty($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attr) {
                            ProductVariantAttribute::create([
                                'product_variant_id' => $variant->id,
                                'attribute_id' => $attr['attribute_id'],
                                'option_id' => $attr['option_id'] ?? null,
                                'value' => $attr['value'] ?? null,
                            ]);
                        }
                    }

                    // Handle variant images
                    if (!empty($variantData['images'])) {
                        foreach ($variantData['images'] as $index => $item) {
                            $url = is_array($item) ? ($item['url'] ?? '') : $item;
                            ProductVariantImage::create([
                                'product_variant_id' => $variant->id,
                                'url' => $url,
                                'type' => is_array($item) ? ($item['type'] ?? 'image') : 'image',
                                'sort_order' => is_array($item) ? ($item['sort_order'] ?? $index) : $index,
                            ]);
                        }
                    }
                }
            }

            if (!empty($data['images'])) {
                $product->images()->delete();
                foreach ($data['images'] as $index => $item) {
                    $url = is_array($item) ? ($item['url'] ?? '') : $item;
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $url,
                        'type' => is_array($item) ? ($item['type'] ?? 'image') : 'image',
                        'sort_order' => is_array($item) ? ($item['sort_order'] ?? $index) : $index,
                    ]);
                }
            }

            return $product->fresh(['variants.attributes', 'variants.images', 'images', 'currency']);
        });
    }

    public function deleteForStore(?int $storeId, int $productId): bool
    {
        $query = Product::where('id', $productId);
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        $product = $query->first();
        if (!$product) {
            return false;
        }

        return DB::transaction(function () use ($product) {
            $product->variants()->delete();
            $product->images()->delete();
            return $product->delete();
        });
    }

    public function findForStore(?int $storeId, int $productId): ?Product
    {
        $query = Product::with(['variants.attributes', 'variants.images', 'images', 'currency'])->where('id', $productId);
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }
        return $query->first();
    }

    public function hasActiveOrders(int $productId): bool
    {
        return SubOrder::whereHas('items', function ($q) use ($productId) {
            $q->whereHas('variant', function ($v) use ($productId) {
                $v->where('product_id', $productId);
            });
        })
        ->whereNotIn('status', [
            OrderStatus::Delivered->value,
            OrderStatus::Cancelled->value,
            OrderStatus::FailedDelivery->value,
        ])
        ->exists();
    }
}
