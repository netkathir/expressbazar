<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('role_name', 'admin')->value('id');

        if (! $adminRoleId) {
            return;
        }

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $adminRoleId,
                'module_name' => 'Coupon Management',
            ],
            [
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('module_name', 'Coupon Management')
            ->delete();
    }
};
