<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\Role;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_valid_details_and_image(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendor = $this->vendor();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'product_name' => 'Premium Rice',
            'description' => 'Long grain rice',
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'price' => '120.00',
            'inventory_mode' => 'internal',
            'stock_quantity' => 25,
            'unit' => 'kg',
            'low_stock_threshold' => 5,
            'status' => 'active',
            'images' => [
                $this->fakePng('rice.png'),
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'product_name' => 'Premium Rice',
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'price' => '120.00',
            'final_price' => '120.00',
            'inventory_mode' => 'internal',
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $product = Product::where('product_name', 'Premium Rice')->firstOrFail();

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => $product->id,
            'inventory_mode' => 'internal',
            'stock_quantity' => 25,
            'unit' => 'kg',
            'low_stock_threshold' => 5,
        ]);

        $image = ProductImage::where('product_id', $product->id)->firstOrFail();
        $this->assertStringStartsWith('storage/uploads/products/', $image->image_path);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $image->image_path));

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Premium Rice');
    }

    public function test_admin_product_create_requires_core_fields(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [])
            ->assertSessionHasErrors([
                'product_name',
                'category_id',
                'vendor_id',
                'price',
                'inventory_mode',
                'stock_quantity',
                'unit',
                'images',
                'status',
            ]);
    }

    public function test_admin_product_create_rejects_invalid_price(): void
    {
        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendor = $this->vendor();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendor, [
                'price' => '0',
            ]))
            ->assertSessionHasErrors(['price']);
    }

    public function test_admin_product_create_rejects_low_stock_threshold_above_stock_quantity(): void
    {
        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendor = $this->vendor();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendor, [
                'stock_quantity' => 3,
                'low_stock_threshold' => 4,
            ]))
            ->assertSessionHasErrors(['low_stock_threshold']);
    }

    public function test_admin_product_create_requires_an_image(): void
    {
        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendor = $this->vendor();
        $payload = $this->validPayload($category, $vendor);
        unset($payload['images']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['images']);

        $this->assertDatabaseMissing('products', [
            'product_name' => 'Premium Rice',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_admin_product_create_rejects_subcategory_from_another_category(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $otherCategory = Category::create(['category_name' => 'Fresh Food', 'status' => 'active']);
        $subcategory = Subcategory::create([
            'category_id' => $otherCategory->id,
            'subcategory_name' => 'Vegetables',
            'status' => 'active',
        ]);
        $vendor = $this->vendor();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendor, [
                'subcategory_id' => $subcategory->id,
            ]))
            ->assertSessionHasErrors(['subcategory_id']);
    }

    public function test_admin_product_name_uniqueness_is_per_vendor(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendorOne = $this->vendor('one');
        $vendorTwo = $this->vendor('two');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendorOne, [
                'product_name' => 'Shared Product',
                'images' => [$this->fakePng('shared-one.png')],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendorTwo, [
                'product_name' => 'Shared Product',
                'images' => [$this->fakePng('shared-two.png')],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(2, Product::where('product_name', 'Shared Product')->count());
    }

    public function test_admin_product_create_rejects_too_many_or_invalid_images(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);
        $vendor = $this->vendor();

        $tooManyImages = array_map(
            fn (int $index) => $this->fakePng("product-{$index}.png"),
            range(1, 6)
        );

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendor, [
                'images' => $tooManyImages,
            ]))
            ->assertSessionHasErrors(['images']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload($category, $vendor, [
                'product_name' => 'Invalid Image Product',
                'images' => [UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf')],
            ]))
            ->assertSessionHasErrors(['images.0']);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Category $category, Vendor $vendor, array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Premium Rice',
            'description' => 'Long grain rice',
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'price' => '120.00',
            'inventory_mode' => 'internal',
            'stock_quantity' => 25,
            'unit' => 'kg',
            'low_stock_threshold' => 5,
            'status' => 'active',
            'images' => [
                $this->fakePng('product.png'),
            ],
        ], $overrides);
    }

    private function adminUser(): User
    {
        $role = Role::create([
            'role_name' => 'admin',
            'description' => 'Full access',
            'status' => 'active',
        ]);

        return User::factory()->create([
            'role' => $role->role_name,
            'status' => 'active',
        ]);
    }

    private function vendor(string $suffix = 'one'): Vendor
    {
        $country = Country::create([
            'country_name' => 'Test Country '.$suffix,
            'country_code' => 'TC'.strtoupper($suffix),
            'currency' => 'GBP',
            'status' => 'active',
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'city_name' => 'Test City '.$suffix,
            'city_code' => 'TCY'.strtoupper($suffix),
            'status' => 'active',
        ]);
        $zone = RegionZone::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'zone_name' => 'Central '.$suffix,
            'zone_code' => 'CENTRAL'.strtoupper($suffix),
            'status' => 'active',
        ]);

        return Vendor::create([
            'vendor_name' => 'Vendor '.$suffix,
            'email' => 'vendor-'.$suffix.'@example.test',
            'phone' => '1234567890',
            'address' => 'Test address',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'region_zone_id' => $zone->id,
            'inventory_mode' => 'internal',
            'status' => 'active',
            'role' => 'vendor',
            'password' => Hash::make('password'),
            'is_setup_complete' => true,
        ]);
    }
}
