<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TestVendorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = collect([
            'coconut stores' => Vendor::query()
                ->where('vendor_name', 'like', 'coconut store%')
                ->where('status', 'active')
                ->latest('id')
                ->first(),
            'KBS stores' => Vendor::query()
                ->where('vendor_name', 'KBS stores')
                ->where('status', 'active')
                ->first(),
            'Pondy Fresh Mart Pvt Ltd' => Vendor::query()
                ->where('vendor_name', 'Pondy Fresh Mart Pvt Ltd')
                ->where('status', 'active')
                ->first(),
        ])->filter();

        if ($vendors->isEmpty()) {
            $this->command?->warn('No matching active vendors found for the test catalog.');

            return;
        }

        $categories = Category::query()
            ->with('subcategories')
            ->where('status', 'active')
            ->get()
            ->keyBy('category_name');

        $sampleVendor = $vendors->first();
        $tax = Tax::updateOrCreate(
            ['tax_name' => 'GST', 'country_id' => $sampleVendor->country_id],
            [
                'tax_percentage' => 5,
                'region_name' => 'India',
                'status' => 'active',
            ]
        );

        $imagePool = $this->imagePool();

        foreach ($vendors as $vendorName => $vendor) {
            foreach ($this->productsForVendor($vendorName) as $index => $productData) {
                $category = $categories->get($productData['category']);
                $subcategory = $category?->subcategories->firstWhere('subcategory_name', $productData['subcategory'])
                    ?: $category?->subcategories->first();

                if (! $category || ! $subcategory) {
                    continue;
                }

                $price = (float) $productData['price'];
                $discountValue = (float) $productData['discount_value'];
                $finalPrice = $productData['discount_type'] === 'percentage'
                    ? round($price - ($price * $discountValue / 100), 2)
                    : round($price - $discountValue, 2);

                $product = Product::updateOrCreate(
                    [
                        'product_name' => $productData['name'],
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'description' => $productData['description'],
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                        'tax_id' => $tax->id,
                        'price' => $price,
                        'discount_type' => $productData['discount_type'],
                        'discount_value' => $discountValue,
                        'final_price' => max(1, $finalPrice),
                        'discount_start_date' => now()->toDateString(),
                        'discount_end_date' => now()->addDays(45)->toDateString(),
                        'inventory_mode' => $vendor->inventory_mode ?: 'internal',
                        'unit' => $productData['unit'],
                        'is_low_stock' => false,
                        'status' => 'active',
                    ]
                );

                ProductInventory::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'inventory_mode' => $vendor->inventory_mode ?: 'internal',
                        'stock_quantity' => $productData['stock'],
                        'unit' => $productData['unit'],
                        'low_stock_threshold' => 8,
                        'sync_status' => $vendor->inventory_mode === 'epos' ? 'synced' : null,
                        'last_synced_at' => now(),
                    ]
                );

                ProductImage::query()->where('product_id', $product->id)->delete();

                foreach ($this->imagesForProduct($imagePool, $index) as $sortOrder => $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }

            $this->command?->info("Seeded 20 test products for {$vendor->vendor_name} ({$vendor->email}).");
        }
    }

    private function productsForVendor(string $vendorName): array
    {
        $prefix = match ($vendorName) {
            'KBS stores' => 'KBS',
            'Pondy Fresh Mart Pvt Ltd' => 'Pondy',
            default => 'Coconut',
        };

        return [
            ['name' => "{$prefix} Tender Coconut", 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Fruits', 'description' => 'Fresh tender coconut selected for daily hydration.', 'price' => 64, 'discount_type' => 'fixed', 'discount_value' => 4, 'stock' => 90, 'unit' => '1 pc'],
            ['name' => "{$prefix} Banana Robusta", 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Fruits', 'description' => 'Naturally sweet bananas for breakfast and snacks.', 'price' => 58, 'discount_type' => 'percentage', 'discount_value' => 8, 'stock' => 120, 'unit' => '1 dozen'],
            ['name' => "{$prefix} Tomato Local", 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Vegetables', 'description' => 'Fresh local tomatoes for curries and chutneys.', 'price' => 34, 'discount_type' => 'fixed', 'discount_value' => 2, 'stock' => 110, 'unit' => '500 g'],
            ['name' => "{$prefix} Onion Small", 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Vegetables', 'description' => 'Kitchen-ready onions packed for daily cooking.', 'price' => 42, 'discount_type' => 'percentage', 'discount_value' => 5, 'stock' => 140, 'unit' => '1 kg'],
            ['name' => "{$prefix} Cow Milk", 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Milk & Curd', 'description' => 'Fresh milk for tea, coffee, and family meals.', 'price' => 68, 'discount_type' => 'fixed', 'discount_value' => 3, 'stock' => 80, 'unit' => '1 L'],
            ['name' => "{$prefix} Fresh Curd", 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Milk & Curd', 'description' => 'Thick curd suitable for meals and smoothies.', 'price' => 52, 'discount_type' => 'percentage', 'discount_value' => 6, 'stock' => 75, 'unit' => '500 g'],
            ['name' => "{$prefix} Sandwich Bread", 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Bread & Buns', 'description' => 'Soft sliced bread for breakfast and lunch boxes.', 'price' => 46, 'discount_type' => 'fixed', 'discount_value' => 4, 'stock' => 65, 'unit' => '1 pack'],
            ['name' => "{$prefix} Farm Eggs", 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Eggs & Paneer', 'description' => 'Fresh eggs packed safely for home delivery.', 'price' => 72, 'discount_type' => 'percentage', 'discount_value' => 7, 'stock' => 85, 'unit' => '6 pcs'],
            ['name' => "{$prefix} Ponni Rice", 'category' => 'Rice & Grains', 'subcategory' => 'Basmati Rice', 'description' => 'Everyday rice with consistent cooking quality.', 'price' => 389, 'discount_type' => 'fixed', 'discount_value' => 20, 'stock' => 38, 'unit' => '5 kg'],
            ['name' => "{$prefix} Toor Dal", 'category' => 'Rice & Grains', 'subcategory' => 'Staples & Pulses', 'description' => 'Protein-rich dal for sambar and daily meals.', 'price' => 154, 'discount_type' => 'percentage', 'discount_value' => 9, 'stock' => 54, 'unit' => '1 kg'],
            ['name' => "{$prefix} Wheat Atta", 'category' => 'Rice & Grains', 'subcategory' => 'Flour & Poha', 'description' => 'Whole wheat flour for soft chapatis.', 'price' => 228, 'discount_type' => 'fixed', 'discount_value' => 15, 'stock' => 46, 'unit' => '5 kg'],
            ['name' => "{$prefix} Banana Chips", 'category' => 'Snacks & Beverages', 'subcategory' => 'Chips & Namkeen', 'description' => 'Crispy banana chips for tea-time snacking.', 'price' => 86, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 70, 'unit' => '200 g'],
            ['name' => "{$prefix} Filter Coffee", 'category' => 'Snacks & Beverages', 'subcategory' => 'Soft Drinks', 'description' => 'Aromatic filter coffee powder for South Indian coffee.', 'price' => 195, 'discount_type' => 'fixed', 'discount_value' => 12, 'stock' => 44, 'unit' => '250 g'],
            ['name' => "{$prefix} Butter Cookies", 'category' => 'Snacks & Beverages', 'subcategory' => 'Biscuits & Cookies', 'description' => 'Light cookies for quick snacks and lunch boxes.', 'price' => 72, 'discount_type' => 'percentage', 'discount_value' => 6, 'stock' => 96, 'unit' => '1 pack'],
            ['name' => "{$prefix} Dishwash Gel", 'category' => 'Cleaning Essentials', 'subcategory' => 'Dishwash & Scrub', 'description' => 'Grease-cutting dishwash gel for daily cleaning.', 'price' => 118, 'discount_type' => 'fixed', 'discount_value' => 8, 'stock' => 52, 'unit' => '500 ml'],
            ['name' => "{$prefix} Floor Cleaner", 'category' => 'Cleaning Essentials', 'subcategory' => 'Floor & Bath Cleaners', 'description' => 'Fresh fragrance floor cleaner for home use.', 'price' => 184, 'discount_type' => 'percentage', 'discount_value' => 12, 'stock' => 40, 'unit' => '1 L'],
            ['name' => "{$prefix} Coconut Hair Oil", 'category' => 'Hair Care', 'subcategory' => 'Hair Oil', 'description' => 'Nourishing coconut hair oil for everyday care.', 'price' => 142, 'discount_type' => 'fixed', 'discount_value' => 10, 'stock' => 58, 'unit' => '200 ml'],
            ['name' => "{$prefix} Herbal Shampoo", 'category' => 'Hair Care', 'subcategory' => 'Shampoo & Conditioner', 'description' => 'Gentle herbal shampoo for routine hair wash.', 'price' => 214, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 42, 'unit' => '340 ml'],
            ['name' => "{$prefix} Toothpaste Fresh", 'category' => 'Personal Care', 'subcategory' => 'Oral Care', 'description' => 'Fresh breath toothpaste for family use.', 'price' => 94, 'discount_type' => 'fixed', 'discount_value' => 6, 'stock' => 82, 'unit' => '150 g'],
            ['name' => "{$prefix} Storage Container Set", 'category' => 'Kitchen & Home', 'subcategory' => 'Storage & Containers', 'description' => 'Airtight containers for rice, snacks, and leftovers.', 'price' => 329, 'discount_type' => 'percentage', 'discount_value' => 11, 'stock' => 28, 'unit' => '3 pcs'],
        ];
    }

    private function imagePool(): array
    {
        $directory = public_path('sample-assets/item');

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->sortBy(fn ($file) => $file->getFilename())
            ->map(fn ($file) => '/sample-assets/item/' . rawurlencode($file->getFilename()))
            ->values()
            ->all();
    }

    private function imagesForProduct(array $imagePool, int $index): array
    {
        if ($imagePool === []) {
            return [];
        }

        return array_values(array_unique([
            $imagePool[$index % count($imagePool)],
            $imagePool[($index + 7) % count($imagePool)],
        ]));
    }
}
