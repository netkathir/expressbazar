<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoCatalogSeeder extends Seeder
{
    private array $assetPools = [];

    public function run(): void
    {
        $this->assetPools = $this->prepareSeedAssets();

        $admin = User::where('email', 'admin@expressbazar.local')->first();

        $countries = Country::query()->where('status', 'active')->get()->keyBy('country_code');
        $cities = City::query()->where('status', 'active')->get()->keyBy('city_code');
        $zones = RegionZone::query()->where('status', 'active')->get()->keyBy('zone_code');

        foreach ($zones as $zoneItem) {
            DB::table('delivery_config')->updateOrInsert(
                ['country_id' => $zoneItem->country_id, 'city_id' => $zoneItem->city_id, 'zone_id' => $zoneItem->id],
                [
                    'delivery_available' => true,
                    'delivery_charge' => $this->deliveryChargeForZone($zoneItem->zone_code),
                    'status' => 'active',
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($this->categories() as $categoryData) {
            $category = Category::updateOrCreate(
                ['category_name' => $categoryData['name']],
                [
                    'image_path' => $categoryData['image'],
                    'status' => 'active',
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );

            foreach ($categoryData['subcategories'] as $index => $subcategoryData) {
                Subcategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'subcategory_name' => $subcategoryData['name'],
                    ],
                    [
                        'image_path' => $subcategoryData['image'],
                        'status' => 'active',
                        'created_by' => $admin?->id,
                        'updated_by' => $admin?->id,
                    ]
                );
            }
        }

        foreach ($this->vendors() as $vendorData) {
            $country = $countries[$vendorData['country_code']] ?? null;
            $city = $cities[$vendorData['city_code']] ?? null;
            $zoneModel = $zones[$vendorData['zone_code']] ?? null;

            Vendor::updateOrCreate(
                ['email' => $vendorData['email']],
                [
                    'vendor_name' => $vendorData['name'],
                    'phone' => $vendorData['phone'],
                    'address' => $vendorData['address'],
                    'logo_path' => $vendorData['logo'],
                    'country_id' => $country?->id,
                    'city_id' => $city?->id,
                    'region_zone_id' => $zoneModel?->id,
                    'inventory_mode' => $vendorData['inventory_mode'],
                    'api_url' => $vendorData['inventory_mode'] === 'epos' ? 'https://api.eposnow.com' : null,
                    'api_key' => $vendorData['inventory_mode'] === 'epos' ? 'epos-demo-key' : null,
                    'credentials' => $vendorData['inventory_mode'] === 'epos' ? json_encode(['username' => 'demo', 'password' => 'demo']) : null,
                    'status' => 'active',
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );
        }

        foreach ($this->banners() as $order => $bannerData) {
            Banner::updateOrCreate(
                ['title' => $bannerData['title']],
                [
                    'subtitle' => $bannerData['subtitle'],
                    'image_path' => $bannerData['image'],
                    'link_url' => $bannerData['link'],
                    'status' => 'active',
                    'sort_order' => $order,
                ]
            );
        }

        foreach ($this->customers() as $customerData) {
            $customer = User::updateOrCreate(
                ['email' => $customerData['email']],
                [
                    'name' => $customerData['name'],
                    'username' => $customerData['username'],
                    'phone' => $customerData['phone'],
                    'avatar_path' => $customerData['avatar'],
                    'password' => Hash::make('Customer@1234'),
                    'role' => 'customer',
                    'status' => 'active',
                ]
            );

            $customer->forceFill([
                'email_verified_at' => now(),
            ])->save();

            $sampleCountry = $countries['UK'] ?? $countries->first();
            $sampleCity = $cities['SOU'] ?? $cities->first();
            $sampleZone = $zones['SO16'] ?? $zones->first();

            if (! $customer->addresses()->exists() && $sampleCountry && $sampleCity && $sampleZone) {
                CustomerAddress::create([
                    'user_id' => $customer->id,
                    'label' => 'Home',
                    'recipient_name' => $customer->name,
                    'phone' => $customer->phone,
                    'address_line_1' => '73 Colby Street',
                    'address_line_2' => 'Demo district',
                    'country_id' => $sampleCountry->id,
                    'city_id' => $sampleCity->id,
                    'zone_id' => $sampleZone->id,
                    'postcode' => 'SO16 9RU',
                    'is_default' => true,
                    'status' => 'active',
                ]);
            }
        }

        $tax = Tax::updateOrCreate(
            ['tax_name' => 'VAT'],
            [
                'tax_percentage' => 20,
                'country_id' => $countries->get('UK')?->id,
                'region_name' => 'United Kingdom',
                'status' => 'active',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        $vendors = Vendor::all()->keyBy('email');
        $categories = Category::with('subcategories')->get()->keyBy('category_name');

        $products = $this->products();
        foreach ($products as $productData) {
            $vendor = $vendors[$productData['vendor_email']] ?? null;
            $category = $categories[$productData['category']] ?? null;
            $subcategory = $category?->subcategories->firstWhere('subcategory_name', $productData['subcategory']) ?? $category?->subcategories->first();

            if (! $vendor || ! $category || ! $subcategory) {
                continue;
            }

            $price = (float) $productData['price'];
            $discountValue = (float) $productData['discount_value'];
            $finalPrice = $productData['discount_type'] === 'percentage'
                ? round($price - ($price * $discountValue / 100), 2)
                : round($price - $discountValue, 2);
            $finalPrice = max(0.01, $finalPrice);

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
                    'final_price' => $finalPrice,
                    'discount_start_date' => now()->toDateString(),
                    'discount_end_date' => now()->addDays(30)->toDateString(),
                    'inventory_mode' => $vendor->inventory_mode,
                    'status' => 'active',
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );

            ProductInventory::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'inventory_mode' => $vendor->inventory_mode,
                    'stock_quantity' => $productData['stock'],
                    'unit' => $productData['unit'],
                    'low_stock_threshold' => 5,
                    'sync_status' => $vendor->inventory_mode === 'epos' ? 'synced' : null,
                    'last_synced_at' => now(),
                ]
            );

            ProductImage::query()->where('product_id', $product->id)->delete();
            foreach ($productData['images'] as $sortOrder => $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        $customers = User::where('role', 'customer')->get()->keyBy('email');
        $sampleCustomer = $customers->first();
        $sampleVendor = $vendors->first();
        $sampleProducts = Product::with('inventory')->take(4)->get();

        if ($sampleCustomer && $sampleVendor && $sampleProducts->isNotEmpty()) {
            $this->seedOrders($sampleCustomer, $sampleVendor, $sampleProducts);
        }
    }

    private function prepareSeedAssets(): array
    {
        return [
            'banner' => $this->publishAssetGroup('banner', 10),
            'category' => $this->publishAssetGroup('category', 18),
            'category_banner' => $this->publishAssetGroup('category_banner', 3),
            'item' => $this->publishAssetGroup('item', 40),
            'coupon' => $this->publishAssetGroup('coupon', 6),
            'offer' => $this->publishAssetGroup('OFFER', 10),
            'branch' => $this->publishAssetGroup('branch', 6),
        ];
    }

    private function publishAssetGroup(string $group, int $limit): array
    {
        $sourceDir = $this->sampleImagesRoot() . DIRECTORY_SEPARATOR . $group;

        if (! File::isDirectory($sourceDir)) {
            return [];
        }

        $targetDir = public_path('sample-assets/' . strtolower($group));
        File::ensureDirectoryExists($targetDir);

        $files = collect(File::files($sourceDir))
            ->filter(function ($file) {
                return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true);
            })
            ->sortBy(fn ($file) => $file->getFilename())
            ->values()
            ->take($limit);

        $published = [];

        foreach ($files as $file) {
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $file->getFilename();

            if (! file_exists($targetPath)) {
                copy($file->getPathname(), $targetPath);
            }

            $published[] = '/sample-assets/' . strtolower($group) . '/' . rawurlencode($file->getFilename());
        }

        return $published;
    }

    private function sampleImagesRoot(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'sample images';
    }

    private function assetUrl(string $group, int $index): string
    {
        $assets = $this->assetPools[$group] ?? [];

        if ($assets === []) {
            return '';
        }

        return $assets[$index % count($assets)];
    }

    private function deliveryChargeForZone(string $zoneCode): float
    {
        return match ($zoneCode) {
            'SO16' => 0.00,
            'SO14', 'SW1', 'MU01', 'BLR1', 'MCR1', 'LIV1', 'BIR1', 'LEE1', 'DEL1', 'CHE1', 'HYD1', 'KOL1' => 15.00,
            'E1', 'MU02', 'BLR2' => 20.00,
            default => 20.00,
        };
    }

    private function productImages(int $startIndex): array
    {
        $images = [
            $this->assetUrl('item', $startIndex),
            $this->assetUrl('item', $startIndex + 1),
        ];

        return array_values(array_filter(array_unique($images)));
    }

    private function categories(): array
    {
        return [
            [
                'name' => 'Fruits & Vegetables',
                'image' => $this->assetUrl('category', 0),
                'subcategories' => [
                    ['name' => 'Fresh Fruits', 'image' => $this->assetUrl('category', 1)],
                    ['name' => 'Fresh Vegetables', 'image' => $this->assetUrl('category', 2)],
                    ['name' => 'Exotic & Premium', 'image' => $this->assetUrl('category', 3)],
                ],
            ],
            [
                'name' => 'Dairy, Bread & Eggs',
                'image' => $this->assetUrl('category', 4),
                'subcategories' => [
                    ['name' => 'Milk & Curd', 'image' => $this->assetUrl('category', 5)],
                    ['name' => 'Bread & Buns', 'image' => $this->assetUrl('category', 6)],
                    ['name' => 'Eggs & Paneer', 'image' => $this->assetUrl('category', 7)],
                ],
            ],
            [
                'name' => 'Rice & Grains',
                'image' => $this->assetUrl('category', 8),
                'subcategories' => [
                    ['name' => 'Basmati Rice', 'image' => $this->assetUrl('category', 9)],
                    ['name' => 'Staples & Pulses', 'image' => $this->assetUrl('category', 10)],
                    ['name' => 'Flour & Poha', 'image' => $this->assetUrl('category', 11)],
                ],
            ],
            [
                'name' => 'Snacks & Beverages',
                'image' => $this->assetUrl('category', 12),
                'subcategories' => [
                    ['name' => 'Chips & Namkeen', 'image' => $this->assetUrl('category', 13)],
                    ['name' => 'Soft Drinks', 'image' => $this->assetUrl('category', 14)],
                    ['name' => 'Biscuits & Cookies', 'image' => $this->assetUrl('category', 15)],
                ],
            ],
            [
                'name' => 'Cleaning Essentials',
                'image' => $this->assetUrl('category', 16),
                'subcategories' => [
                    ['name' => 'Laundry Care', 'image' => $this->assetUrl('category', 17)],
                    ['name' => 'Dishwash & Scrub', 'image' => $this->assetUrl('category', 0)],
                    ['name' => 'Floor & Bath Cleaners', 'image' => $this->assetUrl('category', 1)],
                ],
            ],
            [
                'name' => 'Hair Care',
                'image' => $this->assetUrl('category', 2),
                'subcategories' => [
                    ['name' => 'Hair Serum', 'image' => $this->assetUrl('category', 3)],
                    ['name' => 'Hair Oil', 'image' => $this->assetUrl('category', 4)],
                    ['name' => 'Shampoo & Conditioner', 'image' => $this->assetUrl('category', 5)],
                ],
            ],
            [
                'name' => 'Personal Care',
                'image' => $this->assetUrl('category', 6),
                'subcategories' => [
                    ['name' => 'Face Wash', 'image' => $this->assetUrl('category', 7)],
                    ['name' => 'Soaps & Body Wash', 'image' => $this->assetUrl('category', 8)],
                    ['name' => 'Oral Care', 'image' => $this->assetUrl('category', 9)],
                ],
            ],
            [
                'name' => 'Baby Care',
                'image' => $this->assetUrl('category', 10),
                'subcategories' => [
                    ['name' => 'Baby Food', 'image' => $this->assetUrl('category', 11)],
                    ['name' => 'Diapers & Wipes', 'image' => $this->assetUrl('category', 12)],
                    ['name' => 'Baby Bath', 'image' => $this->assetUrl('category', 13)],
                ],
            ],
            [
                'name' => 'Kitchen & Home',
                'image' => $this->assetUrl('category', 14),
                'subcategories' => [
                    ['name' => 'Cookware', 'image' => $this->assetUrl('category', 15)],
                    ['name' => 'Storage & Containers', 'image' => $this->assetUrl('category', 16)],
                    ['name' => 'Air Fresheners', 'image' => $this->assetUrl('category', 17)],
                ],
            ],
            [
                'name' => 'Offer Zone',
                'image' => $this->assetUrl('category', 1),
                'subcategories' => [
                    ['name' => 'Combo Deals', 'image' => $this->assetUrl('category', 0)],
                    ['name' => 'Top Offers', 'image' => $this->assetUrl('category', 2)],
                    ['name' => 'New Launches', 'image' => $this->assetUrl('category', 4)],
                ],
            ],
        ];
    }

    private function vendors(): array
    {
        return [
            [
                'name' => 'Fresh Basket UK',
                'email' => 'vendor1@expressbazar.local',
                'phone' => '020 1111 1111',
                'address' => '73 Colby Street, Southampton',
                'country_code' => 'UK',
                'city_code' => 'SOU',
                'zone_code' => 'SO16',
                'inventory_mode' => 'internal',
                'logo' => $this->assetUrl('branch', 0),
            ],
            [
                'name' => 'Daily Pantry',
                'email' => 'vendor2@expressbazar.local',
                'phone' => '020 2222 2222',
                'address' => '12 Dock Road, London',
                'country_code' => 'UK',
                'city_code' => 'LON',
                'zone_code' => 'SW1',
                'inventory_mode' => 'epos',
                'logo' => $this->assetUrl('branch', 1),
            ],
            [
                'name' => 'Green Valley Store',
                'email' => 'vendor3@expressbazar.local',
                'phone' => '020 3333 3333',
                'address' => '44 Market Street, Mumbai',
                'country_code' => 'IN',
                'city_code' => 'MUM',
                'zone_code' => 'MU01',
                'inventory_mode' => 'internal',
                'logo' => $this->assetUrl('branch', 2),
            ],
            [
                'name' => 'Quick Home Mart',
                'email' => 'vendor4@expressbazar.local',
                'phone' => '020 4444 4444',
                'address' => '8 High Street, Bengaluru',
                'country_code' => 'IN',
                'city_code' => 'BLR',
                'zone_code' => 'BLR1',
                'inventory_mode' => 'epos',
                'logo' => $this->assetUrl('branch', 3),
            ],
            [
                'name' => 'Northern Grocer',
                'email' => 'vendor5@expressbazar.local',
                'phone' => '0161 555 5555',
                'address' => '18 King Street, Manchester',
                'country_code' => 'UK',
                'city_code' => 'MAN',
                'zone_code' => 'MCR1',
                'inventory_mode' => 'internal',
                'logo' => $this->assetUrl('branch', 4),
            ],
            [
                'name' => 'Capital Fresh Market',
                'email' => 'vendor6@expressbazar.local',
                'phone' => '011 6666 6666',
                'address' => '22 Connaught Place, Delhi',
                'country_code' => 'IN',
                'city_code' => 'DEL',
                'zone_code' => 'DEL1',
                'inventory_mode' => 'epos',
                'logo' => $this->assetUrl('branch', 5),
            ],
            [
                'name' => 'Midlands Daily',
                'email' => 'vendor7@expressbazar.local',
                'phone' => '0121 777 7777',
                'address' => '14 Broad Street, Birmingham',
                'country_code' => 'UK',
                'city_code' => 'BIR',
                'zone_code' => 'BIR1',
                'inventory_mode' => 'internal',
                'logo' => $this->assetUrl('branch', 0),
            ],
            [
                'name' => 'Heritage Market',
                'email' => 'vendor8@expressbazar.local',
                'phone' => '040 888 8888',
                'address' => '9 Banjara Hills, Hyderabad',
                'country_code' => 'IN',
                'city_code' => 'HYD',
                'zone_code' => 'HYD1',
                'inventory_mode' => 'epos',
                'logo' => $this->assetUrl('branch', 1),
            ],
        ];
    }

    private function banners(): array
    {
        return [
            [
                'title' => 'Fresh essentials delivered fast',
                'subtitle' => 'Fruit, dairy, and daily groceries for quick shopping.',
                'image' => $this->assetUrl('banner', 0),
                'link' => '/categories/1',
            ],
            [
                'title' => 'Big savings on daily offers',
                'subtitle' => 'New user coupons and app-only offers live now.',
                'image' => $this->assetUrl('banner', 1),
                'link' => '/categories/10',
            ],
            [
                'title' => 'Fresh vegetables and greens',
                'subtitle' => 'Stock your kitchen with daily produce.',
                'image' => $this->assetUrl('category_banner', 0),
                'link' => '/categories/1',
            ],
            [
                'title' => 'Cleaning essentials for the home',
                'subtitle' => 'Laundry, floor care, and kitchen cleaning deals.',
                'image' => $this->assetUrl('category_banner', 1),
                'link' => '/categories/5',
            ],
            [
                'title' => 'Hair care and personal care',
                'subtitle' => 'Serums, shampoos, and grooming basics.',
                'image' => $this->assetUrl('banner', 2),
                'link' => '/categories/6',
            ],
            [
                'title' => 'Weekend combo offers',
                'subtitle' => 'Offer-zone products with curated savings.',
                'image' => $this->assetUrl('offer', 0),
                'link' => '/categories/10',
            ],
        ];
    }

    private function customers(): array
    {
        return [
            [
                'name' => 'Raj Kumar',
                'email' => 'raj.kumar@example.com',
                'username' => 'rajkumar',
                'phone' => '09578618409',
                'avatar' => '/admin-theme/assets/images/avatar/avatar-1.jpg',
            ],
            [
                'name' => 'Anita Rose',
                'email' => 'anita.rose@example.com',
                'username' => 'anitarose',
                'phone' => '09123456789',
                'avatar' => '/admin-theme/assets/images/avatar/avatar-2.jpg',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'username' => 'johnsmith',
                'phone' => '09876543210',
                'avatar' => '/admin-theme/assets/images/avatar/avatar-3.jpg',
            ],
            [
                'name' => 'Sara Khan',
                'email' => 'sara.khan@example.com',
                'username' => 'sarakhan',
                'phone' => '09770011223',
                'avatar' => '/admin-theme/assets/images/avatar/avatar-4.jpg',
            ],
            [
                'name' => 'Vikram Patel',
                'email' => 'vikram.patel@example.com',
                'username' => 'vikrampatel',
                'phone' => '09881112223',
                'avatar' => '/admin-theme/assets/images/avatar/avatar-5.jpg',
            ],
        ];
    }

    private function products(): array
    {
        $items = [
            ['name' => 'Fresh Apples 1 kg', 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Fruits', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Sweet and crisp apples packed for quick delivery.', 'price' => 149, 'discount_type' => 'percentage', 'discount_value' => 12, 'stock' => 42, 'unit' => '1 kg'],
            ['name' => 'Banana Bunch', 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Fruits', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Farm fresh bananas for everyday use.', 'price' => 59, 'discount_type' => 'fixed', 'discount_value' => 5, 'stock' => 60, 'unit' => '1 bunch'],
            ['name' => 'Mango Alphonso', 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Fruits', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Premium mangoes with a rich seasonal taste.', 'price' => 199, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 24, 'unit' => '1 kg'],
            ['name' => 'Carrot Local', 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Vegetables', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Crunchy local carrots for daily cooking.', 'price' => 39, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 80, 'unit' => '500 g'],
            ['name' => 'Tomato Local', 'category' => 'Fruits & Vegetables', 'subcategory' => 'Fresh Vegetables', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Juicy tomatoes for curries and salads.', 'price' => 29, 'discount_type' => 'fixed', 'discount_value' => 3, 'stock' => 90, 'unit' => '500 g'],
            ['name' => 'Milk 1 L', 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Milk & Curd', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Fresh milk for breakfast and cooking.', 'price' => 62, 'discount_type' => 'fixed', 'discount_value' => 2, 'stock' => 120, 'unit' => '1 L'],
            ['name' => 'Curd Family Pack', 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Milk & Curd', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Fresh curd for daily meal prep.', 'price' => 54, 'discount_type' => 'percentage', 'discount_value' => 6, 'stock' => 72, 'unit' => '500 g'],
            ['name' => 'Whole Wheat Bread', 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Bread & Buns', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Soft whole wheat bread sliced for families.', 'price' => 45, 'discount_type' => 'percentage', 'discount_value' => 8, 'stock' => 55, 'unit' => '1 pack'],
            ['name' => 'Egg Tray 6 pcs', 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Eggs & Paneer', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Farm eggs packed for breakfast needs.', 'price' => 48, 'discount_type' => 'fixed', 'discount_value' => 4, 'stock' => 80, 'unit' => '6 pcs'],
            ['name' => 'Paneer Block 200 g', 'category' => 'Dairy, Bread & Eggs', 'subcategory' => 'Eggs & Paneer', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Fresh paneer for home-style cooking.', 'price' => 79, 'discount_type' => 'percentage', 'discount_value' => 5, 'stock' => 40, 'unit' => '200 g'],
            ['name' => 'Basmati Rice 5 kg', 'category' => 'Rice & Grains', 'subcategory' => 'Basmati Rice', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Long grain basmati rice for everyday meals.', 'price' => 499, 'discount_type' => 'percentage', 'discount_value' => 6, 'stock' => 18, 'unit' => '5 kg'],
            ['name' => 'Sona Masoori Rice', 'category' => 'Rice & Grains', 'subcategory' => 'Basmati Rice', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Light everyday rice for home kitchens.', 'price' => 329, 'discount_type' => 'fixed', 'discount_value' => 20, 'stock' => 26, 'unit' => '5 kg'],
            ['name' => 'Moong Dal 1 kg', 'category' => 'Rice & Grains', 'subcategory' => 'Staples & Pulses', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Protein-rich pulses for daily cooking.', 'price' => 129, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 35, 'unit' => '1 kg'],
            ['name' => 'Atta 5 kg', 'category' => 'Rice & Grains', 'subcategory' => 'Flour & Poha', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Whole wheat flour for soft rotis.', 'price' => 219, 'discount_type' => 'fixed', 'discount_value' => 15, 'stock' => 44, 'unit' => '5 kg'],
            ['name' => 'Masala Chips', 'category' => 'Snacks & Beverages', 'subcategory' => 'Chips & Namkeen', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Spicy and crunchy snacks for quick bites.', 'price' => 35, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 90, 'unit' => '1 pack'],
            ['name' => 'Mixture Namkeen', 'category' => 'Snacks & Beverages', 'subcategory' => 'Chips & Namkeen', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Classic tea-time namkeen mix.', 'price' => 49, 'discount_type' => 'fixed', 'discount_value' => 5, 'stock' => 66, 'unit' => '1 pack'],
            ['name' => 'Cola Drink', 'category' => 'Snacks & Beverages', 'subcategory' => 'Soft Drinks', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Refreshing carbonated drink for every meal.', 'price' => 79, 'discount_type' => 'percentage', 'discount_value' => 8, 'stock' => 72, 'unit' => '1 bottle'],
            ['name' => 'Orange Juice', 'category' => 'Snacks & Beverages', 'subcategory' => 'Soft Drinks', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Citrus juice packed for quick refreshment.', 'price' => 99, 'discount_type' => 'fixed', 'discount_value' => 10, 'stock' => 58, 'unit' => '1 bottle'],
            ['name' => 'Butter Cookies', 'category' => 'Snacks & Beverages', 'subcategory' => 'Biscuits & Cookies', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Tea-time cookies with a buttery finish.', 'price' => 65, 'discount_type' => 'percentage', 'discount_value' => 5, 'stock' => 88, 'unit' => '1 pack'],
            ['name' => 'Laundry Liquid', 'category' => 'Cleaning Essentials', 'subcategory' => 'Laundry Care', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Gentle laundry liquid for everyday washing.', 'price' => 249, 'discount_type' => 'fixed', 'discount_value' => 25, 'stock' => 28, 'unit' => '2 L'],
            ['name' => 'Floor Cleaner', 'category' => 'Cleaning Essentials', 'subcategory' => 'Floor & Bath Cleaners', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Powerful floor cleaner with a fresh fragrance.', 'price' => 189, 'discount_type' => 'percentage', 'discount_value' => 15, 'stock' => 34, 'unit' => '1 L'],
            ['name' => 'Dishwash Liquid', 'category' => 'Cleaning Essentials', 'subcategory' => 'Dishwash & Scrub', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Cuts grease and cleans dishes quickly.', 'price' => 109, 'discount_type' => 'fixed', 'discount_value' => 9, 'stock' => 56, 'unit' => '500 ml'],
            ['name' => 'Shampoo 650 ml', 'category' => 'Hair Care', 'subcategory' => 'Shampoo & Conditioner', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Nourishing shampoo for everyday hair care.', 'price' => 199, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 50, 'unit' => '650 ml'],
            ['name' => 'Hair Conditioner', 'category' => 'Hair Care', 'subcategory' => 'Shampoo & Conditioner', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Smooth conditioning for soft hair.', 'price' => 219, 'discount_type' => 'fixed', 'discount_value' => 20, 'stock' => 48, 'unit' => '650 ml'],
            ['name' => 'Hair Serum 50 ml', 'category' => 'Hair Care', 'subcategory' => 'Hair Serum', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Smooth frizz control serum for quick styling.', 'price' => 199, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 25, 'unit' => '50 ml'],
            ['name' => 'Coconut Hair Oil', 'category' => 'Hair Care', 'subcategory' => 'Hair Oil', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Nourishing coconut oil for soft hair.', 'price' => 175, 'discount_type' => 'fixed', 'discount_value' => 20, 'stock' => 31, 'unit' => '100 ml'],
            ['name' => 'Face Wash Gel', 'category' => 'Personal Care', 'subcategory' => 'Face Wash', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Refreshing face wash for daily cleansing.', 'price' => 149, 'discount_type' => 'percentage', 'discount_value' => 12, 'stock' => 40, 'unit' => '100 ml'],
            ['name' => 'Soap Pack', 'category' => 'Personal Care', 'subcategory' => 'Soaps & Body Wash', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Everyday bathing soap family pack.', 'price' => 119, 'discount_type' => 'fixed', 'discount_value' => 10, 'stock' => 64, 'unit' => '4 pcs'],
            ['name' => 'Toothpaste', 'category' => 'Personal Care', 'subcategory' => 'Oral Care', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Fresh breath toothpaste for daily use.', 'price' => 89, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 77, 'unit' => '150 g'],
            ['name' => 'Baby Diapers Large', 'category' => 'Baby Care', 'subcategory' => 'Diapers & Wipes', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Comfort-fit diapers for babies.', 'price' => 399, 'discount_type' => 'fixed', 'discount_value' => 30, 'stock' => 22, 'unit' => '1 pack'],
            ['name' => 'Baby Food Porridge', 'category' => 'Baby Care', 'subcategory' => 'Baby Food', 'vendor_email' => 'vendor2@expressbazar.local', 'description' => 'Nutrition-rich baby food for early meals.', 'price' => 179, 'discount_type' => 'percentage', 'discount_value' => 8, 'stock' => 30, 'unit' => '200 g'],
            ['name' => 'Cookware Pan', 'category' => 'Kitchen & Home', 'subcategory' => 'Cookware', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Everyday non-stick pan for home cooking.', 'price' => 599, 'discount_type' => 'fixed', 'discount_value' => 50, 'stock' => 14, 'unit' => '1 pc'],
            ['name' => 'Storage Container Set', 'category' => 'Kitchen & Home', 'subcategory' => 'Storage & Containers', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Airtight containers for kitchen storage.', 'price' => 329, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 20, 'unit' => '3 pcs'],
            ['name' => 'Air Freshener Spray', 'category' => 'Kitchen & Home', 'subcategory' => 'Air Fresheners', 'vendor_email' => 'vendor4@expressbazar.local', 'description' => 'Fresh fragrance for rooms and living spaces.', 'price' => 149, 'discount_type' => 'fixed', 'discount_value' => 15, 'stock' => 54, 'unit' => '250 ml'],
            ['name' => 'Tea Bags Family Pack', 'category' => 'Snacks & Beverages', 'subcategory' => 'Soft Drinks', 'vendor_email' => 'vendor5@expressbazar.local', 'description' => 'Classic tea bags for Manchester households.', 'price' => 189, 'discount_type' => 'fixed', 'discount_value' => 15, 'stock' => 36, 'unit' => '100 bags'],
            ['name' => 'Whole Grain Pasta', 'category' => 'Rice & Grains', 'subcategory' => 'Flour & Poha', 'vendor_email' => 'vendor5@expressbazar.local', 'description' => 'Healthy pasta for quick weeknight meals.', 'price' => 139, 'discount_type' => 'percentage', 'discount_value' => 10, 'stock' => 28, 'unit' => '500 g'],
            ['name' => 'Basmati Rice Mini Pack', 'category' => 'Rice & Grains', 'subcategory' => 'Basmati Rice', 'vendor_email' => 'vendor6@expressbazar.local', 'description' => 'Small pack of premium rice for Delhi customers.', 'price' => 159, 'discount_type' => 'percentage', 'discount_value' => 8, 'stock' => 40, 'unit' => '1 kg'],
            ['name' => 'Spice Mix Box', 'category' => 'Offer Zone', 'subcategory' => 'Combo Deals', 'vendor_email' => 'vendor6@expressbazar.local', 'description' => 'Useful spice mix bundle for everyday cooking.', 'price' => 249, 'discount_type' => 'fixed', 'discount_value' => 20, 'stock' => 22, 'unit' => '1 box'],
            ['name' => 'Biscuit Value Pack', 'category' => 'Snacks & Beverages', 'subcategory' => 'Biscuits & Cookies', 'vendor_email' => 'vendor7@expressbazar.local', 'description' => 'Affordable biscuit pack for Birmingham shoppers.', 'price' => 79, 'discount_type' => 'fixed', 'discount_value' => 5, 'stock' => 48, 'unit' => '1 pack'],
            ['name' => 'Green Tea Box', 'category' => 'Snacks & Beverages', 'subcategory' => 'Soft Drinks', 'vendor_email' => 'vendor8@expressbazar.local', 'description' => 'Light green tea box for Hyderabad households.', 'price' => 219, 'discount_type' => 'percentage', 'discount_value' => 12, 'stock' => 30, 'unit' => '1 box'],
            ['name' => 'Combo Deal Basket', 'category' => 'Offer Zone', 'subcategory' => 'Combo Deals', 'vendor_email' => 'vendor1@expressbazar.local', 'description' => 'Curated combo basket with daily essentials.', 'price' => 799, 'discount_type' => 'percentage', 'discount_value' => 15, 'stock' => 12, 'unit' => '1 combo'],
            ['name' => 'Top Offer Shampoo', 'category' => 'Offer Zone', 'subcategory' => 'Top Offers', 'vendor_email' => 'vendor3@expressbazar.local', 'description' => 'Featured shampoo offer for this week.', 'price' => 229, 'discount_type' => 'fixed', 'discount_value' => 25, 'stock' => 18, 'unit' => '1 bottle'],
        ];

        $products = [];

        foreach ($items as $index => $item) {
            $products[] = array_merge($item, [
                'images' => $this->productImages($index * 2),
            ]);
        }

        return $products;
    }

    private function seedOrders(User $customer, Vendor $vendor, $products): void
    {
        $items = $products->take(3);

        $firstOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-1001'],
            [
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'total_amount' => 328,
                'delivery_charge' => 0,
                'payment_status' => 'paid',
                'order_status' => 'delivered',
                'placed_at' => now()->subDays(2),
                'notes' => 'Demo order one',
                'created_by' => $customer->id,
                'updated_by' => $customer->id,
            ]
        );

        $firstOrder->items()->delete();
        $firstOrder->items()->createMany([
            [
                'product_id' => $items[0]->id,
                'item_name' => $items[0]->product_name,
                'quantity' => 1,
                'price' => $items[0]->final_price,
                'subtotal' => $items[0]->final_price,
            ],
            [
                'product_id' => $items[1]->id,
                'item_name' => $items[1]->product_name,
                'quantity' => 2,
                'price' => $items[1]->final_price,
                'subtotal' => $items[1]->final_price * 2,
            ],
        ]);

        $firstOrder->payments()->delete();
        $firstOrder->payments()->create([
            'transaction_id' => 'PAY-DEMO-1001',
            'payment_method' => 'cod',
            'amount' => $firstOrder->total_amount,
            'status' => 'paid',
            'gateway_response' => json_encode(['source' => 'seed']),
            'paid_at' => now()->subDays(2),
        ]);

        $secondOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-1002'],
            [
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'total_amount' => 199,
                'delivery_charge' => 20,
                'payment_status' => 'pending',
                'order_status' => 'processing',
                'placed_at' => now()->subDay(),
                'notes' => 'Demo order two',
                'created_by' => $customer->id,
                'updated_by' => $customer->id,
            ]
        );

        $secondOrder->items()->delete();
        $secondOrder->items()->createMany([
            [
                'product_id' => $items[2]->id,
                'item_name' => $items[2]->product_name,
                'quantity' => 1,
                'price' => $items[2]->final_price,
                'subtotal' => $items[2]->final_price,
            ],
        ]);

        $secondOrder->payments()->delete();
        $secondOrder->payments()->create([
            'transaction_id' => 'PAY-DEMO-1002',
            'payment_method' => 'online',
            'amount' => $secondOrder->total_amount,
            'status' => 'pending',
            'gateway_response' => json_encode(['source' => 'seed']),
        ]);
    }
}
