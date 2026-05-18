<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique(['country_id', 'city_name']);
            $table->unique(['country_id', 'state', 'city_name'], 'cities_country_state_city_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropUnique('cities_country_state_city_unique');
            $table->unique(['country_id', 'city_name']);
        });
    }
};
