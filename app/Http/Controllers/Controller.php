<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    protected function redirectToIndex(Request $request, string $routeName, string $successMessage): RedirectResponse
    {
        $returnUrl = (string) $request->input('return_url', '');

        if ($returnUrl !== '' && $this->isSafeReturnUrl($request, $returnUrl)) {
            return redirect()->to($returnUrl)->with('success', $successMessage);
        }

        return redirect()->route($routeName)->with('success', $successMessage);
    }

    private function isSafeReturnUrl(Request $request, string $url): bool
    {
        $appHost = $request->getHost();
        $urlHost = parse_url($url, PHP_URL_HOST);

        if ($urlHost && $appHost && strcasecmp($urlHost, $appHost) !== 0) {
            return false;
        }

        if (! $urlHost && ! str_starts_with($url, '/')) {
            return false;
        }

        return ! str_starts_with($url, '//');
    }
}
