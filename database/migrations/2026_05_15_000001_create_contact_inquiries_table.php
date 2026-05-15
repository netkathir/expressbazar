<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('email', 150);
            $table->string('phone', 30)->nullable();
            $table->string('subject', 160);
            $table->text('message');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
            $table->index('created_at');
        });

        if (Schema::hasTable('roles') && Schema::hasTable('role_permissions')) {
            $adminRoleId = DB::table('roles')
                ->whereRaw('LOWER(role_name) = ?', ['admin'])
                ->value('id');

            if ($adminRoleId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $adminRoleId,
                        'module_name' => 'Contact Inquiries',
                    ],
                    [
                        'can_view' => true,
                        'can_create' => false,
                        'can_edit' => true,
                        'can_delete' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('module_name', 'Contact Inquiries')
                ->delete();
        }

        Schema::dropIfExists('contact_inquiries');
    }
};
