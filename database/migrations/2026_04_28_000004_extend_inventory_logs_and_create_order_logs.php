<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_logs', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('product_inventory_id')->constrained('vendors')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('order_logs')) {
            Schema::create('order_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');

        Schema::table('inventory_logs', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_logs', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
