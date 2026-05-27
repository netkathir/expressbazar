<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\RegionZone;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_only_shows_permitted_modules(): void
    {
        $admin = $this->adminUserWithPermissions('order-manager', [
            'Order Management' => ['view' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Order Management')
            ->assertDontSee('Product Management')
            ->assertDontSee('Vendor Master');
    }

    public function test_admin_direct_routes_and_actions_are_blocked_without_permission(): void
    {
        $admin = $this->adminUserWithPermissions('order-view-only', [
            'Order Management' => ['view' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.orders.create'))
            ->assertForbidden();
    }

    public function test_admin_role_has_full_access_by_default(): void
    {
        $role = Role::create([
            'role_name' => 'admin',
            'description' => 'Full access',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => $role->role_name,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_inactive_admin_role_is_denied_safely(): void
    {
        $role = Role::create([
            'role_name' => 'inactive-manager',
            'description' => 'Inactive manager',
            'status' => 'inactive',
        ]);

        $admin = User::factory()->create([
            'role' => $role->role_name,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_vendor_product_listing_is_limited_to_own_products(): void
    {
        $role = Role::create([
            'role_name' => 'vendor-products',
            'description' => 'Vendor product access',
            'status' => 'active',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'module_name' => 'Product Management',
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);

        [$vendor, $otherVendor] = $this->vendorsForRole($role->role_name);
        $category = Category::create(['category_name' => 'Groceries', 'status' => 'active']);

        Product::create([
            'product_name' => 'Own Vendor Product',
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'price' => 10,
            'final_price' => 10,
            'inventory_mode' => 'internal',
            'status' => 'active',
        ]);

        Product::create([
            'product_name' => 'Other Vendor Product',
            'category_id' => $category->id,
            'vendor_id' => $otherVendor->id,
            'price' => 12,
            'final_price' => 12,
            'inventory_mode' => 'internal',
            'status' => 'active',
        ]);

        $this->actingAs($vendor, 'vendor')
            ->get(route('vendor.products.index'))
            ->assertOk()
            ->assertSee('Own Vendor Product')
            ->assertDontSee('Other Vendor Product');
    }

    /**
     * @param array<string, array<string, bool>> $permissionsByModule
     */
    private function adminUserWithPermissions(string $roleName, array $permissionsByModule): User
    {
        $role = Role::create([
            'role_name' => $roleName,
            'description' => $roleName,
            'status' => 'active',
        ]);

        foreach ($permissionsByModule as $moduleName => $abilities) {
            RolePermission::create([
                'role_id' => $role->id,
                'module_name' => $moduleName,
                'can_view' => (bool) ($abilities['view'] ?? false),
                'can_create' => (bool) ($abilities['create'] ?? false),
                'can_edit' => (bool) ($abilities['edit'] ?? false),
                'can_delete' => (bool) ($abilities['delete'] ?? false),
            ]);
        }

        return User::factory()->create([
            'role' => $role->role_name,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: Vendor, 1: Vendor}
     */
    private function vendorsForRole(string $roleName): array
    {
        $country = Country::create([
            'country_name' => 'Test Country',
            'country_code' => 'TC',
            'currency' => 'GBP',
            'status' => 'active',
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'city_name' => 'Test City',
            'city_code' => 'TCY',
            'status' => 'active',
        ]);
        $zone = RegionZone::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'zone_name' => 'Central',
            'zone_code' => 'CENTRAL',
            'status' => 'active',
        ]);

        $base = [
            'phone' => '1234567890',
            'address' => 'Test address',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'region_zone_id' => $zone->id,
            'inventory_mode' => 'internal',
            'status' => 'active',
            'role' => $roleName,
            'password' => Hash::make('password'),
            'is_setup_complete' => true,
        ];

        return [
            Vendor::create($base + [
                'vendor_name' => 'Vendor One',
                'email' => 'vendor-one@example.test',
            ]),
            Vendor::create($base + [
                'vendor_name' => 'Vendor Two',
                'email' => 'vendor-two@example.test',
            ]),
        ];
    }
}
