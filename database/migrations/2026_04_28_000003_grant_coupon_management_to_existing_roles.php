<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get(['id']);

        foreach ($roles as $role) {
            $commercePermission = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->whereIn('module_name', ['Product Management', 'Order Management', 'Payment Management'])
                ->where('can_view', true)
                ->first();

            if (! $commercePermission) {
                continue;
            }

            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $role->id,
                    'module_name' => 'Coupon Management',
                ],
                [
                    'can_view' => (bool) $commercePermission->can_view,
                    'can_create' => (bool) $commercePermission->can_create,
                    'can_edit' => (bool) $commercePermission->can_edit,
                    'can_delete' => (bool) $commercePermission->can_delete,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('module_name', 'Coupon Management')
            ->delete();
    }
};
