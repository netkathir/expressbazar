<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            if (! Schema::hasColumn('vendors', 'role')) {
                $table->string('role')->default('vendor')->after('password');
            }

            if (! Schema::hasColumn('vendors', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('vendors', 'remember_token')) {
                $table->rememberToken();
            }
        });

        $roleId = DB::table('roles')->updateOrInsert(
            ['role_name' => 'vendor'],
            [
                'description' => 'Default vendor panel role',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = DB::table('roles')->where('role_name', 'vendor')->value('id');

        if (! $roleId) {
            return;
        }

        foreach ($this->vendorPermissions() as $module => $permissions) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'module_name' => $module,
                ],
                [
                    'can_view' => $permissions['view'],
                    'can_create' => $permissions['create'],
                    'can_edit' => $permissions['edit'],
                    'can_delete' => $permissions['delete'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'vendor')->value('id');

        if ($roleId) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'remember_token')) {
                $table->dropRememberToken();
            }

            foreach (['last_login_at', 'role', 'password'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function vendorPermissions(): array
    {
        return [
            'Product Management' => [
                'view' => true,
                'create' => true,
                'edit' => true,
                'delete' => false,
            ],
            'Order Management' => [
                'view' => true,
                'create' => false,
                'edit' => true,
                'delete' => false,
            ],
            'Coupon Management' => [
                'view' => true,
                'create' => true,
                'edit' => true,
                'delete' => false,
            ],
        ];
    }
};
