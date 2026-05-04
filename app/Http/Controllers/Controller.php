<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    protected function deleteFromDatabase(Model $model): ?bool
    {
        if (method_exists($model, 'forceDelete')) {
            return $model->forceDelete();
        }

        return $model->delete();
    }

    protected function prioritizePrefixSearch(Builder $query, array $columns, string $search): void
    {
        $search = trim($search);

        if ($search === '' || $columns === []) {
            return;
        }

        $needle = mb_strtolower($search).'%' ;
        $bindings = array_fill(0, count($columns), $needle);
        $conditions = implode(' OR ', array_map(
            fn (string $column) => "LOWER(COALESCE($column, '')) LIKE ?",
            $columns
        ));

        $query->orderByRaw("CASE WHEN ({$conditions}) THEN 0 ELSE 1 END", $bindings);
    }
}
