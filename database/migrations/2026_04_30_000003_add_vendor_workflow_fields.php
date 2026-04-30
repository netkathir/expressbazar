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
            if (! Schema::hasColumn('vendors', 'setup_token')) {
                $table->string('setup_token')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('vendors', 'is_setup_complete')) {
                $table->boolean('is_setup_complete')->default(false)->after('setup_token');
            }

            if (! Schema::hasColumn('vendors', 'zone_id')) {
                $table->unsignedBigInteger('zone_id')->nullable()->after('region_zone_id');
            }
        });

        DB::table('vendors')
            ->whereNotNull('password')
            ->update(['is_setup_complete' => true]);

        $roleId = DB::table('roles')->where('role_name', 'vendor')->value('id');

        if ($roleId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'module_name' => 'Payment Management',
                ],
                [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['zone_id', 'is_setup_complete', 'setup_token'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $roleId = DB::table('roles')->where('role_name', 'vendor')->value('id');

        if ($roleId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('module_name', 'Payment Management')
                ->delete();
        }
    }
};
