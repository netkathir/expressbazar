<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureIndexExists('cities', 'idx_cities_country_id', ['country_id']);

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

    private function ensureIndexExists(string $table, string $indexName, array $columns): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::getDatabaseName();
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        if ($exists) {
            return;
        }

        $columnList = collect($columns)
            ->map(fn (string $column) => "`{$column}`")
            ->implode(', ');

        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnList})");
    }
};
