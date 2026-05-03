<?php

namespace App\Http\Controllers;

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
}
