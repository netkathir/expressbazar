<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->index('country_id', 'idx_vendors_country');
            $table->index('city_id', 'idx_vendors_city');
            $table->index('pincode', 'idx_vendors_pincode');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('idx_vendors_country');
            $table->dropIndex('idx_vendors_city');
            $table->dropIndex('idx_vendors_pincode');
        });
    }
};
