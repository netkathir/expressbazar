<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Groceries that feel fast, simple, and familiar.');
    }

    public function test_checkout_places_an_order(): void
    {
        $location = Location::create([
            'city' => 'Chennai',
            'pincode' => '600001',
        ]);

        $vendor = Vendor::create([
            'name' => 'Fresh Mart',
            'slug' => 'fresh-mart',
            'address' => 'Chennai',
            'rating' => 4.8,
        ]);

        $vendor->locations()->attach($location->id);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tomato',
            'slug' => 'tomato',
            'description' => 'Fresh tomatoes',
            'sku' => 'TM-001',
            'price' => 0,
            'mrp' => 0,
            'stock_quantity' => 0,
            'rating' => 0,
            'unit' => '1 kg',
            'image_url' => 'images/products/tomato.jpg',
            'is_active' => true,
        ]);

        $vendorProduct = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 30,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'marketplace_cart' => [
                (string) $vendorProduct->id => [
                    'vendor_product_id' => $vendorProduct->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'quantity' => 2,
                    'unit_price' => 30,
                    'image' => asset('images/products/tomato.jpg'),
                ],
            ],
        ])->post(route('checkout.place'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '9999999999',
            'shipping_address' => 'Test Address',
            'pincode' => '600001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'shipping_name' => 'Test User',
            'shipping_phone' => '9999999999',
            'grand_total' => 60,
        ]);
        $this->assertDatabaseHas('vendor_products', [
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'stock' => 8,
        ]);
    }
}
