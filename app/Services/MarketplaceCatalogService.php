<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketplaceCatalogService
{
    public function locations(): array
    {
        if (Schema::hasTable('locations') && Location::query()->exists()) {
            return Location::query()
                ->orderBy('city')
                ->orderBy('pincode')
                ->get()
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'city' => $location->city,
                    'pincode' => $location->pincode,
                    'label' => $location->city . ' - ' . $location->pincode,
                ])
                ->all();
        }

        return [
            ['id' => 1, 'city' => 'Chennai', 'pincode' => '600001', 'label' => 'Chennai - 600001'],
            ['id' => 2, 'city' => 'Chennai', 'pincode' => '600020', 'label' => 'Chennai - 600020'],
            ['id' => 3, 'city' => 'Bangalore', 'pincode' => '560001', 'label' => 'Bangalore - 560001'],
        ];
    }

    public function categories(): array
    {
        if (Schema::hasTable('categories') && Category::query()->exists()) {
            return Category::query()
                ->withCount('subcategories')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'color' => $category->color ?? '#7c3aed',
                    'subcategories_count' => $category->subcategories_count,
                    'image' => $this->categoryImage($category->slug),
                ])
                ->all();
        }

        return [
            ['id' => 1, 'name' => 'Fruits & Vegetables', 'slug' => 'fruits-vegetables', 'description' => 'Fresh grocery sections', 'color' => '#7c3aed', 'subcategories_count' => 2, 'image' => asset('organic/images/category-thumb-1.jpg')],
            ['id' => 2, 'name' => 'Dairy, Bread & Eggs', 'slug' => 'dairy-bread-eggs', 'description' => 'Daily essentials', 'color' => '#7c3aed', 'subcategories_count' => 2, 'image' => asset('organic/images/category-thumb-2.jpg')],
            ['id' => 3, 'name' => 'Atta, Rice, Oil & Dals', 'slug' => 'atta-rice-oil-dals', 'description' => 'Kitchen staples', 'color' => '#7c3aed', 'subcategories_count' => 1, 'image' => asset('organic/images/category-thumb-3.jpg')],
            ['id' => 4, 'name' => 'Masala & Dry Fruits', 'slug' => 'masala-dry-fruits', 'description' => 'Spices and pantry', 'color' => '#7c3aed', 'subcategories_count' => 1, 'image' => asset('organic/images/category-thumb-5.jpg')],
            ['id' => 5, 'name' => 'Breakfast & Sauces', 'slug' => 'breakfast-sauces', 'description' => 'Quick meals and spreads', 'color' => '#7c3aed', 'subcategories_count' => 1, 'image' => asset('organic/images/category-thumb-6.jpg')],
        ];
    }

    public function subcategories(): array
    {
        if (Schema::hasTable('subcategories') && Subcategory::query()->exists()) {
            return Subcategory::query()
                ->with('category')
                ->withCount('products')
                ->orderBy('name')
                ->get()
                ->map(fn (Subcategory $subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'description' => $subcategory->description,
                    'category_name' => $subcategory->category?->name,
                    'category_slug' => $subcategory->category?->slug,
                    'products_count' => $subcategory->products_count,
                ])
                ->all();
        }

        return [
            ['id' => 1, 'name' => 'Vegetables', 'slug' => 'vegetables', 'description' => 'Fresh vegetables', 'category_name' => 'Fresh Produce', 'category_slug' => 'fresh-produce', 'products_count' => 3],
            ['id' => 2, 'name' => 'Fruits', 'slug' => 'fruits', 'description' => 'Seasonal fruits', 'category_name' => 'Fresh Produce', 'category_slug' => 'fresh-produce', 'products_count' => 2],
            ['id' => 3, 'name' => 'Dairy', 'slug' => 'dairy', 'description' => 'Milk and dairy essentials', 'category_name' => 'Dairy & Bakery', 'category_slug' => 'dairy-bread-eggs', 'products_count' => 1],
            ['id' => 4, 'name' => 'Bakery', 'slug' => 'bakery', 'description' => 'Bread and baked goods', 'category_name' => 'Dairy & Bakery', 'category_slug' => 'dairy-bread-eggs', 'products_count' => 1],
            ['id' => 5, 'name' => 'Staples', 'slug' => 'staples', 'description' => 'Kitchen staples', 'category_name' => 'Pantry Staples', 'category_slug' => 'pantry-staples', 'products_count' => 1],
        ];
    }

    public function vendors(): array
    {
        if (Schema::hasTable('vendors') && Vendor::query()->exists()) {
            return Vendor::query()
                ->with('locations')
                ->orderBy('name')
                ->get()
                ->map(fn (Vendor $vendor) => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'slug' => $vendor->slug,
                    'description' => $vendor->description,
                    'address' => $vendor->address,
                    'rating' => (float) $vendor->rating,
                    'locations' => $vendor->locations->map(fn (Location $location) => [
                        'id' => $location->id,
                        'city' => $location->city,
                        'pincode' => $location->pincode,
                    ])->all(),
                ])
                ->all();
        }

        return [
            ['id' => 1, 'name' => 'Fresh Mart', 'slug' => 'fresh-mart', 'description' => 'Quick grocery and daily essentials store.', 'address' => 'Chennai', 'rating' => 4.8, 'locations' => [1, 2]],
            ['id' => 2, 'name' => 'Green Grocery', 'slug' => 'green-grocery', 'description' => 'Neighborhood fruits, vegetables, and milk.', 'address' => 'Chennai', 'rating' => 4.7, 'locations' => [2]],
            ['id' => 3, 'name' => 'Daily Needs Store', 'slug' => 'daily-needs-store', 'description' => 'Fast-moving grocery essentials.', 'address' => 'Bangalore', 'rating' => 4.6, 'locations' => [3]],
        ];
    }

    public function products(): array
    {
        if (Schema::hasTable('products') && Product::query()->exists()) {
            return Product::query()
                ->with(['subcategory.category'])
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => $this->normalizeProduct($product))
                ->all();
        }

        return [
            ['id' => 1, 'name' => 'Tomato', 'slug' => 'tomato', 'description' => 'Fresh tomatoes', 'sku' => 'TM-001', 'image' => asset('images/products/tomato.jpg')],
            ['id' => 2, 'name' => 'Potato', 'slug' => 'potato', 'description' => 'Fresh potatoes', 'sku' => 'PT-001', 'image' => asset('images/products/potato.jpg')],
            ['id' => 3, 'name' => 'Onion', 'slug' => 'onion', 'description' => 'Fresh onions', 'sku' => 'ON-001', 'image' => asset('images/products/onion.jpg')],
            ['id' => 4, 'name' => 'Milk', 'slug' => 'milk', 'description' => 'Dairy milk', 'sku' => 'MK-001', 'image' => asset('images/products/milk.jpg')],
            ['id' => 5, 'name' => 'Bread', 'slug' => 'bread', 'description' => 'Fresh bread', 'sku' => 'BR-001', 'image' => asset('images/products/bread.jpg')],
        ];
    }

    public function vendorProducts(): array
    {
        if (Schema::hasTable('vendor_products') && VendorProduct::query()->exists()) {
            return VendorProduct::query()
                ->with(['vendor', 'product.subcategory'])
                ->orderBy('id', 'desc')
                ->get()
                ->map(fn (VendorProduct $vendorProduct) => [
                    'id' => $vendorProduct->id,
                    'vendor_id' => $vendorProduct->vendor_id,
                    'product_id' => $vendorProduct->product_id,
                    'vendor_name' => $vendorProduct->vendor?->name,
                    'vendor_slug' => $vendorProduct->vendor?->slug,
                    'product_name' => $vendorProduct->product?->name,
                    'product_slug' => $vendorProduct->product?->slug,
                    'description' => $vendorProduct->product?->description,
                    'subcategory_id' => $vendorProduct->product?->subcategory_id,
                    'subcategory_name' => $vendorProduct->product?->subcategory?->name,
                    'subcategory_slug' => $vendorProduct->product?->subcategory?->slug,
                    'category_name' => $vendorProduct->product?->subcategory?->category?->name,
                    'category_slug' => $vendorProduct->product?->subcategory?->category?->slug,
                    'image' => $this->productImage($vendorProduct->product?->image_url ?? null, $vendorProduct->product?->name ?? 'Product'),
                    'price' => (int) $vendorProduct->price,
                    'stock' => (int) $vendorProduct->stock,
                    'is_active' => (bool) $vendorProduct->is_active,
                ])
                ->all();
        }

        return [
            ['id' => 1, 'vendor_id' => 1, 'product_id' => 1, 'vendor_name' => 'Fresh Mart', 'vendor_slug' => 'fresh-mart', 'product_name' => 'Tomato', 'product_slug' => 'tomato', 'description' => 'Fresh tomatoes', 'subcategory_id' => 1, 'subcategory_name' => 'Vegetables', 'subcategory_slug' => 'vegetables', 'category_name' => 'Fresh Produce', 'category_slug' => 'fresh-produce', 'image' => asset('images/products/tomato.jpg'), 'price' => 30, 'stock' => 100, 'is_active' => true],
            ['id' => 2, 'vendor_id' => 1, 'product_id' => 4, 'vendor_name' => 'Fresh Mart', 'vendor_slug' => 'fresh-mart', 'product_name' => 'Milk', 'product_slug' => 'milk', 'description' => 'Dairy milk', 'subcategory_id' => 3, 'subcategory_name' => 'Dairy', 'subcategory_slug' => 'dairy', 'category_name' => 'Dairy & Bakery', 'category_slug' => 'dairy-bread-eggs', 'image' => asset('images/products/milk.jpg'), 'price' => 50, 'stock' => 50, 'is_active' => true],
            ['id' => 3, 'vendor_id' => 2, 'product_id' => 1, 'vendor_name' => 'Green Grocery', 'vendor_slug' => 'green-grocery', 'product_name' => 'Tomato', 'product_slug' => 'tomato', 'description' => 'Fresh tomatoes', 'subcategory_id' => 1, 'subcategory_name' => 'Vegetables', 'subcategory_slug' => 'vegetables', 'category_name' => 'Fresh Produce', 'category_slug' => 'fresh-produce', 'image' => asset('images/products/tomato.jpg'), 'price' => 28, 'stock' => 80, 'is_active' => true],
            ['id' => 4, 'vendor_id' => 2, 'product_id' => 2, 'vendor_name' => 'Green Grocery', 'vendor_slug' => 'green-grocery', 'product_name' => 'Potato', 'product_slug' => 'potato', 'description' => 'Fresh potatoes', 'subcategory_id' => 1, 'subcategory_name' => 'Vegetables', 'subcategory_slug' => 'vegetables', 'category_name' => 'Fresh Produce', 'category_slug' => 'fresh-produce', 'image' => asset('images/products/potato.jpg'), 'price' => 25, 'stock' => 120, 'is_active' => true],
            ['id' => 5, 'vendor_id' => 3, 'product_id' => 5, 'vendor_name' => 'Daily Needs Store', 'vendor_slug' => 'daily-needs-store', 'product_name' => 'Bread', 'product_slug' => 'bread', 'description' => 'Fresh bread', 'subcategory_id' => 4, 'subcategory_name' => 'Bakery', 'subcategory_slug' => 'bakery', 'category_name' => 'Dairy & Bakery', 'category_slug' => 'dairy-bread-eggs', 'image' => asset('images/products/bread.jpg'), 'price' => 40, 'stock' => 60, 'is_active' => true],
            ['id' => 6, 'vendor_id' => 3, 'product_id' => 4, 'vendor_name' => 'Daily Needs Store', 'vendor_slug' => 'daily-needs-store', 'product_name' => 'Milk', 'product_slug' => 'milk', 'description' => 'Dairy milk', 'subcategory_id' => 3, 'subcategory_name' => 'Dairy', 'subcategory_slug' => 'dairy', 'category_name' => 'Dairy & Bakery', 'category_slug' => 'dairy-bread-eggs', 'image' => asset('images/products/milk.jpg'), 'price' => 55, 'stock' => 30, 'is_active' => true],
        ];
    }

    public function categoryBySlug(string $slug): ?array
    {
        return collect($this->categories())->firstWhere('slug', $slug);
    }

    public function subcategoryBySlug(string $slug): ?array
    {
        return collect($this->subcategories())->firstWhere('slug', $slug);
    }

    public function couponByCode(string $code): ?array
    {
        if (! Schema::hasTable('coupons')) {
            return null;
        }

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if (! $coupon) {
            return null;
        }

        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (int) $coupon->value,
            'min_order_amount' => (int) $coupon->min_order_amount,
            'max_discount_amount' => $coupon->max_discount_amount !== null ? (int) $coupon->max_discount_amount : null,
            'starts_at' => $coupon->starts_at,
            'ends_at' => $coupon->ends_at,
            'is_active' => (bool) $coupon->is_active,
        ];
    }

    public function productsForCategory(string $categorySlug, ?int $locationId = null): array
    {
        return collect($this->productsForLocation($locationId))
            ->filter(fn (array $item) => ($item['category_slug'] ?? null) === $categorySlug)
            ->values()
            ->all();
    }

    public function productsForSubcategory(string $subcategorySlug, ?int $locationId = null): array
    {
        return collect($this->productsForLocation($locationId))
            ->filter(fn (array $item) => ($item['subcategory_slug'] ?? null) === $subcategorySlug)
            ->values()
            ->all();
    }

    public function vendorsForLocation(?int $locationId): array
    {
        if (! $locationId) {
            return $this->vendors();
        }

        return collect($this->vendors())
            ->filter(fn (array $vendor) => collect($vendor['locations'] ?? [])->contains(fn (mixed $location) => (int) ($location['id'] ?? $location) === $locationId))
            ->values()
            ->all();
    }

    public function productsForVendor(int $vendorId): array
    {
        return collect($this->vendorProducts())
            ->filter(fn (array $item) => (int) $item['vendor_id'] === $vendorId && ($item['is_active'] ?? true))
            ->values()
            ->all();
    }

    public function productsForLocation(?int $locationId): array
    {
        $vendors = $this->vendorsForLocation($locationId);
        $vendorIds = collect($vendors)->pluck('id')->all();

        return collect($this->vendorProducts())
            ->filter(fn (array $item) => in_array((int) $item['vendor_id'], $vendorIds, true) && ($item['is_active'] ?? true))
            ->values()
            ->all();
    }

    public function findVendorBySlug(string $slug): ?array
    {
        return collect($this->vendors())->firstWhere('slug', $slug);
    }

    public function findProductBySlug(string $slug): ?array
    {
        return collect($this->products())->firstWhere('slug', $slug);
    }

    public function findVendorProduct(int $vendorId, int $productId): ?array
    {
        return collect($this->vendorProducts())
            ->first(fn (array $item) => (int) $item['vendor_id'] === $vendorId && (int) $item['product_id'] === $productId);
    }

    public function productSummary(Product|array $product): array
    {
        if ($product instanceof Model) {
            $product = $this->normalizeProduct($product);
        }

        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'description' => $product['description'] ?? '',
            'sku' => $product['sku'] ?? '',
            'image' => $this->productImage($product['image_url'] ?? null, $product['name']),
        ];
    }

    private function normalizeProduct(Product|array $product): array
    {
        if ($product instanceof Product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'sku' => $product->sku,
                'image' => $this->productImage($product->image_url, $product->name),
                'image_url' => $product->image_url,
                'subcategory_id' => $product->subcategory_id,
                'subcategory_slug' => $product->subcategory?->slug,
                'subcategory_name' => $product->subcategory?->name,
                'category_slug' => $product->subcategory?->category?->slug,
                'category_name' => $product->subcategory?->category?->name,
            ];
        }

        return $product;
    }

    private function productImage(?string $imageUrl, string $label): string
    {
        if ($imageUrl) {
            return asset($imageUrl);
        }

        $slug = Str::slug($label);
        return asset('images/products/' . $slug . '.jpg');
    }

    private function categoryImage(string $slug): string
    {
        $map = [
            'fresh-produce' => asset('organic/images/category-thumb-1.jpg'),
            'dairy-bakery' => asset('organic/images/category-thumb-2.jpg'),
            'pantry-staples' => asset('organic/images/category-thumb-3.jpg'),
            'spices-dry-fruits' => asset('organic/images/category-thumb-5.jpg'),
            'breakfast-sauces' => asset('organic/images/category-thumb-6.jpg'),
        ];

        return $map[$slug] ?? asset('organic/images/category-thumb-1.jpg');
    }
}
