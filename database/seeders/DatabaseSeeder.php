<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::firstOrCreate(
            ['email' => 'admin@expressbazar.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $locations = [
            ['city' => 'Chennai', 'pincode' => '600001'],
            ['city' => 'Chennai', 'pincode' => '600020'],
            ['city' => 'Bangalore', 'pincode' => '560001'],
        ];

        foreach ($locations as $data) {
            Location::updateOrCreate(['pincode' => $data['pincode']], $data);
        }

        $vendors = [
            ['name' => 'Fresh Mart', 'slug' => 'fresh-mart', 'description' => 'Daily grocery and fresh essentials.', 'address' => 'Chennai', 'rating' => 4.8],
            ['name' => 'Green Grocery', 'slug' => 'green-grocery', 'description' => 'Fruit and vegetable focused store.', 'address' => 'Chennai', 'rating' => 4.7],
            ['name' => 'Daily Needs Store', 'slug' => 'daily-needs-store', 'description' => 'Fast-moving essentials and pantry items.', 'address' => 'Bangalore', 'rating' => 4.6],
        ];

        foreach ($vendors as $data) {
            Vendor::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $vendorMap = Vendor::query()->pluck('id', 'slug');
        $locationMap = Location::query()->pluck('id', 'pincode');
        $categoryMap = [];

        $categories = [
            ['name' => 'Fresh Produce', 'slug' => 'fresh-produce', 'description' => 'Vegetables and fruits.', 'color' => '#7c3aed'],
            ['name' => 'Dairy & Bakery', 'slug' => 'dairy-bread-eggs', 'description' => 'Milk, bread, eggs, and more.', 'color' => '#2563eb'],
            ['name' => 'Pantry Staples', 'slug' => 'pantry-staples', 'description' => 'Rice, atta, oil, and dals.', 'color' => '#059669'],
            ['name' => 'Masala & Dry Fruits', 'slug' => 'masala-dry-fruits', 'description' => 'Spices, herbs, and dry fruits.', 'color' => '#f97316'],
            ['name' => 'Breakfast & Sauces', 'slug' => 'breakfast-sauces', 'description' => 'Quick morning essentials.', 'color' => '#db2777'],
        ];

        foreach ($categories as $data) {
            $category = Category::updateOrCreate(['slug' => $data['slug']], $data);
            $categoryMap[$data['slug']] = $category->id;
        }

        $subcategories = [
            ['category_slug' => 'fresh-produce', 'name' => 'Vegetables', 'slug' => 'vegetables', 'description' => 'Fresh vegetables.'],
            ['category_slug' => 'fresh-produce', 'name' => 'Fruits', 'slug' => 'fruits', 'description' => 'Seasonal fruits.'],
            ['category_slug' => 'dairy-bread-eggs', 'name' => 'Dairy', 'slug' => 'dairy', 'description' => 'Milk and dairy essentials.'],
            ['category_slug' => 'dairy-bread-eggs', 'name' => 'Bakery', 'slug' => 'bakery', 'description' => 'Bread and baked goods.'],
            ['category_slug' => 'pantry-staples', 'name' => 'Staples', 'slug' => 'staples', 'description' => 'Kitchen staples.'],
        ];

        foreach ($subcategories as $data) {
            Subcategory::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categoryMap[$data['category_slug']],
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            );
        }

        $subcategoryMap = Subcategory::query()->pluck('id', 'slug');

        $vendorLocations = [
            'fresh-mart' => ['600001', '600020'],
            'green-grocery' => ['600020'],
            'daily-needs-store' => ['560001'],
        ];

        foreach ($vendorLocations as $vendorSlug => $pincodes) {
            $vendor = Vendor::find($vendorMap[$vendorSlug]);
            $vendor->locations()->sync(array_map(fn (string $pincode) => $locationMap[$pincode], $pincodes));
        }

        $products = [
            ['name' => 'Tomato', 'slug' => 'tomato', 'description' => 'Fresh tomatoes', 'sku' => 'TM-001', 'subcategory_slug' => 'vegetables', 'price' => 30, 'mrp' => 35, 'image_url' => 'images/products/tomato.jpg'],
            ['name' => 'Potato', 'slug' => 'potato', 'description' => 'Fresh potatoes', 'sku' => 'PT-001', 'subcategory_slug' => 'vegetables', 'price' => 25, 'mrp' => 29, 'image_url' => 'images/products/potato.jpg'],
            ['name' => 'Onion', 'slug' => 'onion', 'description' => 'Fresh onions', 'sku' => 'ON-001', 'subcategory_slug' => 'vegetables', 'price' => 23, 'mrp' => 28, 'image_url' => 'images/products/onion.jpg'],
            ['name' => 'Milk', 'slug' => 'milk', 'description' => 'Dairy milk', 'sku' => 'MK-001', 'subcategory_slug' => 'dairy', 'price' => 50, 'mrp' => 55, 'image_url' => 'images/products/milk.jpg'],
            ['name' => 'Bread', 'slug' => 'bread', 'description' => 'Fresh bread', 'sku' => 'BR-001', 'subcategory_slug' => 'bakery', 'price' => 40, 'mrp' => 45, 'image_url' => 'images/products/bread.jpg'],
        ];

        $firstVendorId = $vendorMap['fresh-mart'];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'vendor_id' => $firstVendorId,
                    'subcategory_id' => $subcategoryMap[$data['subcategory_slug']],
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'mrp' => $data['mrp'],
                    'stock_quantity' => 0,
                    'rating' => 0,
                    'unit' => '1 pc',
                    'deal_text' => null,
                    'accent_color' => '#0f766e',
                    'background_color' => '#eef2ff',
                    'image_url' => $data['image_url'],
                    'is_active' => true,
                ]
            );
        }

        $productMap = Product::query()->pluck('id', 'slug');

        $vendorProducts = [
            ['vendor_slug' => 'fresh-mart', 'product_slug' => 'tomato', 'price' => 30, 'stock' => 100],
            ['vendor_slug' => 'fresh-mart', 'product_slug' => 'milk', 'price' => 50, 'stock' => 50],
            ['vendor_slug' => 'green-grocery', 'product_slug' => 'tomato', 'price' => 28, 'stock' => 80],
            ['vendor_slug' => 'green-grocery', 'product_slug' => 'potato', 'price' => 25, 'stock' => 120],
            ['vendor_slug' => 'daily-needs-store', 'product_slug' => 'bread', 'price' => 40, 'stock' => 60],
            ['vendor_slug' => 'daily-needs-store', 'product_slug' => 'milk', 'price' => 55, 'stock' => 30],
        ];

        foreach ($vendorProducts as $data) {
            VendorProduct::updateOrCreate(
                [
                    'vendor_id' => $vendorMap[$data['vendor_slug']],
                    'product_id' => $productMap[$data['product_slug']],
                ],
                [
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                    'is_active' => true,
                ]
            );
        }

        $coupons = [
            ['code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10, 'min_order_amount' => 100, 'max_discount_amount' => 100, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'is_active' => true],
            ['code' => 'SAVE50', 'type' => 'fixed', 'value' => 50, 'min_order_amount' => 200, 'max_discount_amount' => 50, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'is_active' => true],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(['code' => $coupon['code']], $coupon);
        }
    }
}
