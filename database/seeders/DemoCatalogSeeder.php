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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@expressbazar.local')->first();

        $country = Country::where('country_code', 'UK')->first();
        $city = City::where('city_name', 'Southampton')->first();
        $zone = RegionZone::where('zone_code', 'SO16')->first();

        if ($country && $city && $zone) {
            RegionZone::updateOrCreate(
                ['zone_code' => 'SO14', 'city_id' => $city->id],
                [
                    'country_id' => $country->id,
                    'zone_name' => 'SO14 Area',
                    'delivery_available' => true,
                    'status' => 'active',
                ]
            );

            RegionZone::updateOrCreate(
                ['zone_code' => 'SO15', 'city_id' => $city->id],
                [
                    'country_id' => $country->id,
                    'zone_name' => 'SO15 Area',
                    'delivery_available' => true,
                    'status' => 'active',
                ]
            );
        }

        $zones = RegionZone::query()->where('status', 'active')->get()->keyBy('zone_code');

        foreach ($zones as $zoneItem) {
            DB::table('delivery_config')->updateOrInsert(
                ['country_id' => $zoneItem->country_id, 'city_id' => $zoneItem->city_id, 'zone_id' => $zoneItem->id],
                [
                    'delivery_available' => true,
                    'delivery_charge' => $zoneItem->zone_code === 'SO16' ? 0.00 : 20.00,
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

        foreach ($this->vendors($country, $city, $zones) as $vendorData) {
            $zoneModel = $zones[$vendorData['zone_code']] ?? null;

            Vendor::updateOrCreate(
                ['email' => $vendorData['email']],
                [
                    'vendor_name' => $vendorData['name'],
                    'phone' => $vendorData['phone'],
                    'address' => $vendorData['address'],
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

            if (! $customer->addresses()->exists() && $country && $city && $zone) {
                CustomerAddress::create([
                    'user_id' => $customer->id,
                    'label' => 'Home',
                    'recipient_name' => $customer->name,
                    'phone' => $customer->phone,
                    'address_line_1' => '73 Colby Street',
                    'address_line_2' => 'Southampton',
                    'country_id' => $country->id,
                    'city_id' => $city->id,
                    'zone_id' => $zone->id,
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
                'country_id' => $country?->id,
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

    private function categories(): array
    {
        return [
            [
                'name' => 'Fruits & Vegetables',
                'image' => '/admin-theme/assets/images/product-1.png',
                'subcategories' => [
                    ['name' => 'Fresh Fruits', 'image' => '/admin-theme/assets/images/product-2.png'],
                    ['name' => 'Fresh Vegetables', 'image' => '/admin-theme/assets/images/product-3.png'],
                ],
            ],
            [
                'name' => 'Dairy, Bread & Eggs',
                'image' => '/admin-theme/assets/images/product-4.png',
                'subcategories' => [
                    ['name' => 'Milk & Curd', 'image' => '/admin-theme/assets/images/product-5.png'],
                    ['name' => 'Bread & Buns', 'image' => '/admin-theme/assets/images/product-6.png'],
                ],
            ],
            [
                'name' => 'Cleaning Essentials',
                'image' => '/admin-theme/assets/images/product-7.png',
                'subcategories' => [
                    ['name' => 'Floor Cleaners', 'image' => '/admin-theme/assets/images/product-8.png'],
                    ['name' => 'Laundry Care', 'image' => '/admin-theme/assets/images/product-9.png'],
                ],
            ],
            [
                'name' => 'Hair Care',
                'image' => '/admin-theme/assets/images/product-10.png',
                'subcategories' => [
                    ['name' => 'Hair Serum', 'image' => '/admin-theme/assets/images/product-1.png'],
                    ['name' => 'Hair Oil', 'image' => '/admin-theme/assets/images/product-2.png'],
                ],
            ],
            [
                'name' => 'Rice & Grains',
                'image' => '/admin-theme/assets/images/product-3.png',
                'subcategories' => [
                    ['name' => 'Basmati Rice', 'image' => '/admin-theme/assets/images/product-4.png'],
                    ['name' => 'Staples', 'image' => '/admin-theme/assets/images/product-5.png'],
                ],
            ],
            [
                'name' => 'Snacks & Drinks',
                'image' => '/admin-theme/assets/images/product-6.png',
                'subcategories' => [
                    ['name' => 'Beverages', 'image' => '/admin-theme/assets/images/product-7.png'],
                    ['name' => 'Chips & Snacks', 'image' => '/admin-theme/assets/images/product-8.png'],
                ],
            ],
        ];
    }

    private function vendors(?Country $country, ?City $city, $zones): array
    {
        return [
            [
                'name' => 'Fresh Basket UK',
                'email' => 'vendor1@expressbazar.local',
                'phone' => '020 1111 1111',
                'address' => '73 Colby Street, Southampton',
                'zone_code' => 'SO16',
                'inventory_mode' => 'internal',
            ],
            [
                'name' => 'Daily Pantry',
                'email' => 'vendor2@expressbazar.local',
                'phone' => '020 2222 2222',
                'address' => '12 Dock Road, Southampton',
                'zone_code' => 'SO14',
                'inventory_mode' => 'epos',
            ],
            [
                'name' => 'Green Valley Store',
                'email' => 'vendor3@expressbazar.local',
                'phone' => '020 3333 3333',
                'address' => '44 Market Street, Southampton',
                'zone_code' => 'SO15',
                'inventory_mode' => 'internal',
            ],
            [
                'name' => 'Quick Home Mart',
                'email' => 'vendor4@expressbazar.local',
                'phone' => '020 4444 4444',
                'address' => '8 High Street, Southampton',
                'zone_code' => 'SO16',
                'inventory_mode' => 'epos',
            ],
        ];
    }

    private function banners(): array
    {
        return [
            [
                'title' => 'Fresh essentials delivered fast',
                'subtitle' => 'Fruit, dairy, and daily groceries for quick shopping.',
                'image' => '/admin-theme/assets/images/product-1.png',
                'link' => '/categories/1',
            ],
            [
                'title' => 'Home care and cleaning offers',
                'subtitle' => 'Keep your home stocked with trusted brands.',
                'image' => '/admin-theme/assets/images/product-7.png',
                'link' => '/categories/3',
            ],
            [
                'title' => 'Hair care favorites',
                'subtitle' => 'Top picks from quick commerce style shelves.',
                'image' => '/admin-theme/assets/images/product-10.png',
                'link' => '/categories/4',
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
        ];
    }

    private function products(): array
    {
        return [
            [
                'name' => 'Fresh Apples 1 kg',
                'category' => 'Fruits & Vegetables',
                'subcategory' => 'Fresh Fruits',
                'vendor_email' => 'vendor1@expressbazar.local',
                'description' => 'Sweet and crisp apples packed for quick delivery.',
                'price' => 149,
                'discount_type' => 'percentage',
                'discount_value' => 12,
                'stock' => 42,
                'unit' => '1 kg',
                'images' => [
                    '/admin-theme/assets/images/product-1.png',
                    '/admin-theme/assets/images/product-2.png',
                ],
            ],
            [
                'name' => 'Banana Bunch',
                'category' => 'Fruits & Vegetables',
                'subcategory' => 'Fresh Fruits',
                'vendor_email' => 'vendor1@expressbazar.local',
                'description' => 'Farm fresh bananas for everyday use.',
                'price' => 59,
                'discount_type' => 'fixed',
                'discount_value' => 5,
                'stock' => 60,
                'unit' => '1 bunch',
                'images' => [
                    '/admin-theme/assets/images/product-2.png',
                    '/admin-theme/assets/images/product-3.png',
                ],
            ],
            [
                'name' => 'Carrot Local',
                'category' => 'Fruits & Vegetables',
                'subcategory' => 'Fresh Vegetables',
                'vendor_email' => 'vendor3@expressbazar.local',
                'description' => 'Crunchy local carrots for daily cooking.',
                'price' => 39,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'stock' => 80,
                'unit' => '500 g',
                'images' => [
                    '/admin-theme/assets/images/product-3.png',
                    '/admin-theme/assets/images/product-4.png',
                ],
            ],
            [
                'name' => 'Milk 1 L',
                'category' => 'Dairy, Bread & Eggs',
                'subcategory' => 'Milk & Curd',
                'vendor_email' => 'vendor2@expressbazar.local',
                'description' => 'Fresh milk for breakfast and cooking.',
                'price' => 62,
                'discount_type' => 'fixed',
                'discount_value' => 2,
                'stock' => 120,
                'unit' => '1 L',
                'images' => [
                    '/admin-theme/assets/images/product-4.png',
                    '/admin-theme/assets/images/product-5.png',
                ],
            ],
            [
                'name' => 'Whole Wheat Bread',
                'category' => 'Dairy, Bread & Eggs',
                'subcategory' => 'Bread & Buns',
                'vendor_email' => 'vendor2@expressbazar.local',
                'description' => 'Soft whole wheat bread sliced for families.',
                'price' => 45,
                'discount_type' => 'percentage',
                'discount_value' => 8,
                'stock' => 55,
                'unit' => '1 pack',
                'images' => [
                    '/admin-theme/assets/images/product-5.png',
                    '/admin-theme/assets/images/product-6.png',
                ],
            ],
            [
                'name' => 'Lavender Floor Cleaner',
                'category' => 'Cleaning Essentials',
                'subcategory' => 'Floor Cleaners',
                'vendor_email' => 'vendor4@expressbazar.local',
                'description' => 'Powerful floor cleaner with a fresh fragrance.',
                'price' => 189,
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'stock' => 34,
                'unit' => '1 L',
                'images' => [
                    '/admin-theme/assets/images/product-7.png',
                    '/admin-theme/assets/images/product-8.png',
                ],
            ],
            [
                'name' => 'Laundry Liquid',
                'category' => 'Cleaning Essentials',
                'subcategory' => 'Laundry Care',
                'vendor_email' => 'vendor4@expressbazar.local',
                'description' => 'Gentle laundry liquid for everyday washing.',
                'price' => 249,
                'discount_type' => 'fixed',
                'discount_value' => 25,
                'stock' => 28,
                'unit' => '2 L',
                'images' => [
                    '/admin-theme/assets/images/product-8.png',
                    '/admin-theme/assets/images/product-9.png',
                ],
            ],
            [
                'name' => 'Hair Serum 50 ml',
                'category' => 'Hair Care',
                'subcategory' => 'Hair Serum',
                'vendor_email' => 'vendor3@expressbazar.local',
                'description' => 'Smooth frizz control serum for quick styling.',
                'price' => 199,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'stock' => 25,
                'unit' => '50 ml',
                'images' => [
                    '/admin-theme/assets/images/product-10.png',
                    '/admin-theme/assets/images/product-1.png',
                ],
            ],
            [
                'name' => 'Coconut Hair Oil',
                'category' => 'Hair Care',
                'subcategory' => 'Hair Oil',
                'vendor_email' => 'vendor3@expressbazar.local',
                'description' => 'Nourishing coconut oil for soft hair.',
                'price' => 175,
                'discount_type' => 'fixed',
                'discount_value' => 20,
                'stock' => 31,
                'unit' => '100 ml',
                'images' => [
                    '/admin-theme/assets/images/product-2.png',
                    '/admin-theme/assets/images/product-10.png',
                ],
            ],
            [
                'name' => 'Basmati Rice 5 kg',
                'category' => 'Rice & Grains',
                'subcategory' => 'Basmati Rice',
                'vendor_email' => 'vendor1@expressbazar.local',
                'description' => 'Long grain basmati rice for everyday meals.',
                'price' => 499,
                'discount_type' => 'percentage',
                'discount_value' => 6,
                'stock' => 18,
                'unit' => '5 kg',
                'images' => [
                    '/admin-theme/assets/images/product-3.png',
                    '/admin-theme/assets/images/product-4.png',
                ],
            ],
            [
                'name' => 'Tonic Water',
                'category' => 'Snacks & Drinks',
                'subcategory' => 'Beverages',
                'vendor_email' => 'vendor2@expressbazar.local',
                'description' => 'Refreshing beverage for everyday consumption.',
                'price' => 89,
                'discount_type' => 'fixed',
                'discount_value' => 9,
                'stock' => 64,
                'unit' => '1 bottle',
                'images' => [
                    '/admin-theme/assets/images/product-6.png',
                    '/admin-theme/assets/images/product-7.png',
                ],
            ],
            [
                'name' => 'Masala Chips',
                'category' => 'Snacks & Drinks',
                'subcategory' => 'Chips & Snacks',
                'vendor_email' => 'vendor2@expressbazar.local',
                'description' => 'Spicy and crunchy snacks for quick bites.',
                'price' => 35,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'stock' => 90,
                'unit' => '1 pack',
                'images' => [
                    '/admin-theme/assets/images/product-8.png',
                    '/admin-theme/assets/images/product-9.png',
                ],
            ],
        ];
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
