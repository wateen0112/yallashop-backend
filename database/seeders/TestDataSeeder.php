<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\CategoryAttribute;
use App\Modules\Catalog\Models\CategoryAttributeOption;
use App\Modules\Catalog\Models\Currency;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\ProductVariantAttribute;
use App\Modules\Delivery\Models\Courier;
use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Models\CartItem;
use App\Modules\Users\Models\CustomerProfile;
use App\Modules\Users\Models\User;
use App\Modules\Vendors\Models\Store;
use App\Support\Enums\StoreStatus;
use App\Support\Enums\UserRole;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@yallashop.com',
            'phone' => '+966500000000',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);
        $admin->assignRole('admin');

        $vendorUser = User::create([
            'name' => 'Test Vendor',
            'email' => 'vendor@yallashop.com',
            'phone' => '+966500000001',
            'password' => 'password',
            'role' => UserRole::Vendor,
        ]);
        $vendorUser->assignRole('vendor');

        $customerUser = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@yallashop.com',
            'phone' => '+966500000002',
            'password' => 'password',
            'role' => UserRole::Customer,
        ]);
        $customerUser->assignRole('customer');

        $courierUser = User::create([
            'name' => 'Test Courier',
            'email' => 'courier@yallashop.com',
            'phone' => '+966500000003',
            'password' => 'password',
            'role' => UserRole::Courier,
        ]);
        $courierUser->assignRole('courier');

        $store = Store::create([
            'owner_user_id' => $vendorUser->id,
            'name' => 'Test Store',
            'slug' => 'test-store',
            'description' => 'A test store for development',
            'status' => StoreStatus::Approved,
            'commission_rate' => 10.0,
            'city' => 'Riyadh',
        ]);

        $fashion = Category::create(['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Fashion and clothing', 'sort_order' => 1]);
        $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronics and gadgets', 'sort_order' => 2]);
        $homeLiving = Category::create(['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Home and living products', 'sort_order' => 3]);

        $fashion->children()->createMany([
            ['name' => 'Men\'s Fashion', 'slug' => 'mens-fashion', 'sort_order' => 1],
            ['name' => 'Women\'s Fashion', 'slug' => 'womens-fashion', 'sort_order' => 2],
        ]);

        $electronics->children()->createMany([
            ['name' => 'Mobile Phones', 'slug' => 'mobile-phones', 'sort_order' => 1],
            ['name' => 'Laptops', 'slug' => 'laptops', 'sort_order' => 2],
        ]);

        $homeLiving->children()->createMany([
            ['name' => 'Furniture', 'slug' => 'furniture', 'sort_order' => 1],
            ['name' => 'Kitchen', 'slug' => 'kitchen', 'sort_order' => 2],
        ]);

        $defaultCurrency = Currency::where('is_default', true)->first();
        $sizes = ['S', 'M', 'L', 'XL'];
        $colors = ['Black', 'White', 'Red', 'Blue'];

        $sizeAttribute = CategoryAttribute::create([
            'category_id' => $fashion->id,
            'name' => 'Size',
            'type' => 'select',
            'is_required' => true,
            'sort_order' => 1,
        ]);
        $sizeOptions = collect($sizes)->map(fn ($value, $i) => CategoryAttributeOption::create([
            'attribute_id' => $sizeAttribute->id,
            'value' => $value,
            'sort_order' => $i + 1,
        ]));

        $colorAttribute = CategoryAttribute::create([
            'category_id' => $fashion->id,
            'name' => 'Color',
            'type' => 'color',
            'is_required' => true,
            'sort_order' => 2,
        ]);
        $colorOptions = collect($colors)->map(fn ($value, $i) => CategoryAttributeOption::create([
            'attribute_id' => $colorAttribute->id,
            'value' => $value,
            'sort_order' => $i + 1,
        ]));

        $productData = [
            ['title' => 'Classic Cotton T-Shirt', 'base_price' => 49.99],
            ['title' => 'Wireless Bluetooth Headphones', 'base_price' => 199.99],
            ['title' => 'Stainless Steel Water Bottle', 'base_price' => 29.99],
            ['title' => 'Leather Wallet', 'base_price' => 79.99],
            ['title' => 'Running Shoes', 'base_price' => 149.99],
        ];

        foreach ($productData as $i => $pData) {
            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $fashion->id,
                'title' => $pData['title'],
                'description' => "High quality {$pData['title']} - perfect for everyday use.",
                'base_price' => $pData['base_price'],
                'currency_id' => $defaultCurrency?->id,
                'status' => 'active',
                'rating_avg' => round(3.5 + (float) rand() / getrandmax() * 1.5, 1),
            ]);

            foreach ($sizes as $size) {
                foreach (array_slice($colors, 0, 2) as $color) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(substr(str_replace(' ', '', $pData['title']), 0, 8)) . "-{$size}-{$color}",
                        'price' => $pData['base_price'] + ($size === 'XL' ? 10 : ($size === 'L' ? 5 : 0)),
                        'stock_qty' => rand(10, 100),
                    ]);

                    $sizeOption = $sizeOptions->firstWhere('value', $size);
                    $colorOption = $colorOptions->firstWhere('value', $color);
                    ProductVariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $sizeAttribute->id,
                        'option_id' => $sizeOption?->id,
                        'value' => $size,
                    ]);
                    ProductVariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $colorAttribute->id,
                        'option_id' => $colorOption?->id,
                        'value' => $color,
                    ]);
                }
            }

            ProductImage::create([
                'product_id' => $product->id,
                'url' => "https://picsum.photos/seed/product{$i}/400/400",
                'sort_order' => 0,
            ]);
            ProductImage::create([
                'product_id' => $product->id,
                'url' => "https://picsum.photos/seed/product{$i}b/400/400",
                'sort_order' => 1,
            ]);
        }

        CustomerProfile::create([
            'user_id' => $customerUser->id,
            'wallet_balance' => 100.00,
            'loyalty_points' => 50,
        ]);

        $cart = Cart::create(['user_id' => $customerUser->id]);
        $firstProduct = Product::first();
        $firstVariant = $firstProduct->variants()->first();
        if ($firstVariant) {
            CartItem::create([
                'cart_id' => $cart->id,
                'variant_id' => $firstVariant->id,
                'qty' => 2,
            ]);
        }
        $secondProduct = Product::skip(1)->first();
        $secondVariant = $secondProduct->variants()->first();
        if ($secondVariant) {
            CartItem::create([
                'cart_id' => $cart->id,
                'variant_id' => $secondVariant->id,
                'qty' => 1,
            ]);
        }

        Courier::create([
            'user_id' => $courierUser->id,
            'vehicle_type' => 'motorcycle',
            'status' => 'online',
            'current_lat' => 24.7136,
            'current_lng' => 46.6753,
            'wallet_balance' => 0,
        ]);
    }
}
