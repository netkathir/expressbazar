<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('country_name')->unique();
            $table->string('country_code', 10)->unique();
            $table->string('currency', 20);
            $table->string('timezone', 100)->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('state')->nullable();
            $table->string('city_name');
            $table->string('city_code', 50)->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['country_id', 'city_name']);
        });

        Schema::create('regions_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->string('zone_name');
            $table->string('zone_code', 100)->nullable();
            $table->boolean('delivery_available')->default(true);
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['city_id', 'zone_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions_zones');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
