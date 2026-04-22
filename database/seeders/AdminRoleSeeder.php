<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'Country Management',
            'City Management',
            'Region / Zone Management',
            'Vendor Management',
            'Category Management',
            'Subcategory Management',
            'Customer Management',
            'Tax Management',
            'Product Management',
            'Inventory Management',
            'Order Management',
            'Payment Management',
            'Delivery & Logistics',
            'Notification Management',
            'Reports & Analytics',
            'User & Role Management',
            'Admin User Management',
            'System Configuration',
        ];

        $role = Role::updateOrCreate(
            ['role_name' => 'admin'],
            [
                'description' => 'Full access administrator role',
                'status' => 'active',
            ]
        );

        foreach ($modules as $moduleName) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_name' => $moduleName,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                ]
            );
        }
    }
}
